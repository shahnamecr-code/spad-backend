<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Services\SpadNeighbors as Neighbors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpadNeighborsController extends Controller
{
    private const STATES = [
        'posts' => ['pending', 'published', 'rejected', 'hidden', 'deleted'],
        'comments' => ['pending', 'published', 'rejected', 'hidden', 'deleted'],
        'reports' => ['open', 'resolved', 'dismissed'],
        'appeals' => ['open', 'accepted', 'rejected'],
        'residencies' => ['pending', 'verified', 'denied', 'revoked'],
    ];

    public function index(Request $request, string $group)
    {
        $this->admin();
        abort_unless($group === 'audit' || isset(self::STATES[$group]), 404);
        $table = $group === 'audit' ? 'audits' : $group;
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATES[$group] ?? [])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $query = DB::table('spad_neighbor_'.$table.' as item')->select('item.*');
        if (in_array($group, ['posts', 'residencies'], true)) {
            $query->join('regions', 'regions.id', '=', 'item.region_id')->addSelect('regions.name as region');
        }
        if (!empty($filters['status'])) $query->where('item.status', $filters['status']);
        $page = $query->orderByDesc('item.id')->paginate(25);
        if ($group === 'posts') {
            $media = DB::table('spad_neighbor_media')->whereIn('post_id', $page->getCollection()->pluck('id'))
                ->get(['id','post_id'])->groupBy('post_id');
            $page->getCollection()->each(function ($post) use ($media) {
                $post->media = $media->get($post->id, collect())->map(function ($image) {
                    return ['id' => $image->id, 'path' => '/api/admin/neighbors/media/'.$image->id];
                })->values();
            });
        }
        return $this->response($page->toArray());
    }

    public function moderate(Request $request, string $group, int $id)
    {
        $admin = $this->admin();
        abort_unless(isset(self::STATES[$group]), 404);
        $states = array_values(array_diff(self::STATES[$group], ['deleted']));
        $input = $request->validate([
            'status' => ['required', Rule::in($states)],
            'note' => [Rule::requiredIf(in_array($request->input('status'), ['verified', 'rejected', 'hidden', 'denied', 'revoked'], true)), 'nullable', 'string', 'max:500'],
        ]);
        return DB::transaction(function () use ($group, $id, $input, $admin) {
            $table = 'spad_neighbor_'.$group;
            $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            abort_unless($row && $row->status !== 'deleted', 404);
            if ($row->status === $input['status']) return $this->response(['status' => true]);
            $this->transition($group, $row, $input['status'], $admin, $input['note'] ?? null);
            return $this->response(['status' => true]);
        }, 3);
    }

    public function media(Request $request, int $id)
    {
        $this->admin();
        $row = DB::table('spad_neighbor_media')->where('id', $id)->first();
        abort_unless($row && Storage::disk('local')->exists($row->path), 404);
        return response()->file(Storage::disk('local')->path($row->path), [
            'Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    private function transition(string $group, $record, string $status, Admin $admin, ?string $note)
    {
        $now = now();
        $attributes = ['status' => $status, 'updated_at' => $now];
        if ($status === 'published') {
            if ($group === 'posts') {
                $title = 'یک مطلب تازه در محلهٔ دنبال‌شده منتشر شد.';
                Neighbors::preventPrivateContact($record->title, 'title');
                Neighbors::preventPrivateContact($record->body, 'body');
                $attributes['published_at'] = $record->published_at ?? $now;
            } elseif ($group === 'comments') {
                Neighbors::preventPrivateContact($record->body, 'body');
                abort_unless(DB::table('spad_neighbor_posts')->where('id', $record->post_id)->where('status', 'published')->exists(), 422, 'ابتدا مطلب اصلی را منتشر کنید.');
            }
        }
        if ($group === 'appeals' && $status === 'accepted') {
            $target = $record->target_type === 'post' ? 'posts' : 'comments';
            $content = DB::table('spad_neighbor_'.$target)->where('id', $record->target_id)->lockForUpdate()->first();
            abort_unless($content && in_array($content->status, ['rejected', 'hidden'], true), 422, 'وضعیت محتوا برای انتشار دوباره معتبر نیست.');
            $this->transition($target, $content, 'published', $admin, $note);
        }
        DB::table('spad_neighbor_'.$group)->where('id', $record->id)->update($attributes);
        DB::table('spad_neighbor_audits')->insert([
            'admin_id' => $admin->id, 'resource' => $group, 'record_id' => $record->id,
            'from_status' => $record->status, 'to_status' => $status, 'note' => $note,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        if ($status === 'published' && $group === 'posts') {
            DB::table('spad_neighbor_follows as follow')
                ->join('spad_neighbor_preferences as pref', 'pref.user_id', '=', 'follow.user_id')
                ->join('users', 'users.id', '=', 'follow.user_id')->whereNull('users.deleted_at')->where('users.status', User::APPROVED)
                ->where('follow.region_id', $record->region_id)->where('follow.user_id', '<>', $record->author_id)
                ->where('pref.notify_followed', true)->select('follow.id', 'follow.user_id')
                ->chunkById(100, function ($followers) use ($record, $title) {
                    foreach ($followers as $follower) Neighbors::notice((int) $follower->user_id, 'published-'.$record->id, $title, (int) $record->id);
                }, 'follow.id', 'id');
        }
        if ($status === 'published' && $group === 'comments') {
            $target = $record->parent_id
                ? DB::table('spad_neighbor_comments')->where('id', $record->parent_id)->where('status', 'published')->value('author_id')
                : DB::table('spad_neighbor_posts')->where('id', $record->post_id)->value('author_id');
            if ($target && $target != $record->author_id && DB::table('spad_neighbor_preferences')->where('user_id', $target)->where('notify_replies', true)->exists()) {
                Neighbors::notice((int) $target, 'comment-'.$record->id, 'پاسخ تازه‌ای در اسپاد همسایه دارید.', (int) $record->post_id);
            }
        }
    }

    private function admin(): Admin
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin && (int) $admin->status === Admin::APPROVED
            && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
        Neighbors::installed();
        return $admin;
    }

    private function response(array $data)
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
