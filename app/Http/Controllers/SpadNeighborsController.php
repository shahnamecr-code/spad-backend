<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadNeighbors as Neighbors;
use App\Support\SpadLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpadNeighborsController extends Controller
{
    private const PAGE = 18;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Neighbors::installed();
            return $next($request);
        });
    }

    public function regions()
    {
        $city = SpadLocation::city();
        return ['data' => ['city' => ['id' => $city->id, 'name' => $city->name, 'province_id' => $city->province_id],
            'regions' => Neighbors::regions()->orderBy('name')->get(['id', 'name']),
            'categories' => Neighbors::CATEGORIES]];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'region_id' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', Rule::in(array_keys(Neighbors::CATEGORIES))],
            'q' => ['nullable', 'string', 'max:100'],
            'before_id' => ['nullable', 'integer', 'min:1'],
            'lost_status' => ['nullable', Rule::in(['lost', 'found', 'resolved'])],
        ]);
        if (!empty($filters['region_id'])) Neighbors::region((int) $filters['region_id']);
        $query = $this->published();
        foreach (['region_id', 'category', 'lost_status'] as $key) {
            if (isset($filters[$key])) $query->where('post.'.$key, $filters[$key]);
        }
        if (!empty($filters['before_id'])) $query->where('post.id', '<', $filters['before_id']);
        $term = str_replace(['%', '_', '\\'], '', $filters['q'] ?? '');
        if ($term !== '') {
            $query->where(function ($filter) use ($term) {
                $filter->where('post.title', 'like', '%'.$term.'%')->orWhere('post.body', 'like', '%'.$term.'%');
            });
        }
        return $this->postsPage($query, $this->viewer($request));
    }

    public function show(Request $request, int $id)
    {
        $post = $this->published()->where('post.id', $id)->first();
        abort_unless($post, 404);
        return $this->privateResponse(['data' => $this->presentPosts(collect([$post]), $this->viewer($request))->first()]);
    }

    public function comments(Request $request, int $id)
    {
        $this->visiblePost($id);
        $cursor = $request->validate(['before_id' => ['nullable', 'integer', 'min:1']]);
        $rows = $this->publishedComments()->where('comment.post_id', $id)
            ->when(isset($cursor['before_id']), function ($query) use ($cursor) {
                $query->where('comment.id', '<', $cursor['before_id']);
            })->orderByDesc('comment.id')->limit(self::PAGE + 1)
            ->get(['comment.id', 'comment.parent_id', 'comment.post_id', 'comment.author_id',
                'comment.body', 'comment.created_at', 'comment_author.first_name']);
        $viewer = $this->viewer($request);
        return $this->page($rows, function ($row) use ($viewer) {
            return ['id' => $row->id, 'parent_id' => $row->parent_id, 'body' => $row->body,
                'author' => $this->displayName($row->first_name), 'own' => (bool) ($viewer && $viewer->id == $row->author_id),
                'created_at' => $row->created_at];
        });
    }

    public function me(Request $request)
    {
        $user = Neighbors::account($request->user());
        $prefs = DB::table('spad_neighbor_preferences')->where('user_id', $user->id)->first();
        return $this->privateResponse(['data' => [
            'profile' => ['name' => $this->displayName($user->first_name), 'phone_verified' => !!$user->phone_verified_at],
            'active_region_id' => $prefs->active_region_id ?? null,
            'followed_regions' => DB::table('spad_neighbor_follows')->where('user_id', $user->id)->orderBy('region_id')->pluck('region_id'),
            'residencies' => DB::table('spad_neighbor_residencies')->where('user_id', $user->id)->get(['region_id', 'status']),
            'notify_replies' => (bool) ($prefs->notify_replies ?? false),
            'notify_followed' => (bool) ($prefs->notify_followed ?? false),
            'unread' => DB::table('spad_neighbor_notices')->where('user_id', $user->id)->whereNull('read_at')->count(),
        ]]);
    }

    public function preferences(Request $request)
    {
        $user = Neighbors::account($request->user());
        $data = $request->validate([
            'active_region_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'notify_replies' => ['sometimes', 'boolean'], 'notify_followed' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['active_region_id'])) Neighbors::region((int) $data['active_region_id']);
        if ($data) {
            $now = now();
            $data['updated_at'] = $now;
            DB::table('spad_neighbor_preferences')->upsert(
                [$data + ['user_id' => $user->id, 'created_at' => $now]], ['user_id'], array_keys($data)
            );
        }
        return $this->me($request);
    }

    public function follow(Request $request, int $region)
    {
        $user = Neighbors::account($request->user());
        Neighbors::region($region);
        $query = DB::table('spad_neighbor_follows');
        if ($request->isMethod('DELETE')) {
            $query->where('user_id', $user->id)->where('region_id', $region)->delete();
        } else {
            $query->insertOrIgnore(['user_id' => $user->id, 'region_id' => $region, 'created_at' => now(), 'updated_at' => now()]);
        }
        return $this->me($request);
    }

    public function residency(Request $request, int $region)
    {
        $user = $this->writer($request);
        Neighbors::region($region);
        $request->validate(['consent' => ['required', 'accepted']]);
        DB::table('spad_neighbor_residencies')->insertOrIgnore([
            'user_id' => $user->id, 'region_id' => $region, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->me($request);
    }

    public function mine(Request $request)
    {
        $user = Neighbors::account($request->user());
        $data = $request->validate(['before_id' => ['nullable', 'integer', 'min:1']]);
        return $this->postsPage($this->postQuery()->where('post.author_id', $user->id)->where('post.status', '<>', 'deleted')
            ->when(isset($data['before_id']), function ($query) use ($data) {
                $query->where('post.id', '<', $data['before_id']);
            }), $user);
    }

    public function myComments(Request $request)
    {
        $user = Neighbors::account($request->user());
        return $this->privateResponse(['data' => DB::table('spad_neighbor_comments')
            ->where('author_id', $user->id)->where('status', '<>', 'deleted')->orderByDesc('id')
            ->limit(50)->get(['id', 'post_id', 'parent_id', 'body', 'status', 'created_at'])]);
    }

    public function bookmarks(Request $request)
    {
        $user = Neighbors::account($request->user());
        $data = $request->validate(['before_id' => ['nullable', 'integer', 'min:1']]);
        return $this->postsPage($this->published()->whereExists(function ($query) use ($user) {
            $query->selectRaw('1')->from('spad_neighbor_interactions')->whereColumn('post_id', 'post.id')
                ->where('user_id', $user->id)->where('kind', 'bookmark');
        })->when(isset($data['before_id']), function ($query) use ($data) {
            $query->where('post.id', '<', $data['before_id']);
        }), $user);
    }

    public function save(Request $request, ?int $id = null)
    {
        $user = $this->writer($request);
        $data = $request->validate([
            'region_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:3000'],
            'client_key' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/D'],
            'category' => ['required', Rule::in(array_keys(Neighbors::CATEGORIES))],
            'lost_status' => [Rule::requiredIf($request->input('category') === 'lost-found'), 'nullable', Rule::in(['lost', 'found', 'resolved'])],
            'event_at' => [Rule::requiredIf($request->input('category') === 'events'), 'nullable', 'date_format:Y-m-d\TH:i'],
        ]);
        Neighbors::region((int) $data['region_id']);
        Neighbors::preventPrivateContact($data['title'], 'title');
        Neighbors::preventPrivateContact($data['body'], 'body');
        $data['lost_status'] = $data['category'] === 'lost-found' ? $data['lost_status'] : null;
        $data['event_at'] = $data['category'] === 'events' ? str_replace('T', ' ', $data['event_at']).':00' : null;
        $data['status'] = 'pending';
        $data['updated_at'] = now();
        if ($id === null) {
            $posts = DB::table('spad_neighbor_posts');
            $values = $data + ['author_id' => $user->id, 'created_at' => now()];
            if (empty($data['client_key'])) {
                $id = $posts->insertGetId($values);
            } else {
                $posts->insertOrIgnore($values);
                $saved = $posts->where('author_id', $user->id)->where('client_key', $data['client_key'])->first(['id', 'status']);
                abort_unless($saved, 503, 'ذخیرهٔ نوشته انجام نشد.');
                return $this->privateResponse(['data' => ['id' => $saved->id, 'status' => $saved->status]]);
            }
        } else {
            unset($data['client_key']);
            DB::transaction(function () use ($id, $user, $data) {
                $post = DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->lockForUpdate()->first();
                abort_unless($post && $post->status !== 'deleted', 404);
                DB::table('spad_neighbor_posts')->where('id', $id)->update($data);
            }, 3);
        }
        return $this->privateResponse(['data' => ['id' => $id, 'status' => 'pending']]);
    }

    public function delete(Request $request, int $id)
    {
        $user = Neighbors::account($request->user());
        $this->ownerPost($id, $user);
        DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->where('status', '<>', 'deleted')
            ->update(['status' => 'deleted', 'updated_at' => now()]);
        return $this->privateResponse(['status' => true]);
    }

    public function resolveLost(Request $request, int $id)
    {
        $user = Neighbors::account($request->user());
        $post = $this->ownerPost($id, $user);
        abort_unless($post->category === 'lost-found', 422);
        DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->where('status', '<>', 'deleted')
            ->update(['lost_status' => 'resolved', 'updated_at' => now()]);
        return $this->privateResponse(['status' => true]);
    }

    public function comment(Request $request, int $id)
    {
        $user = $this->writer($request);
        $this->visiblePost($id);
        $data = $request->validate(['body' => ['required', 'string', 'max:1200'], 'parent_id' => ['nullable', 'integer', 'min:1']]);
        Neighbors::preventPrivateContact($data['body'], 'body');
        if (isset($data['parent_id'])) {
            abort_unless($this->publishedComments()->where('comment.id', $data['parent_id'])
                ->where('comment.post_id', $id)->whereNull('comment.parent_id')->exists(), 404);
        }
        $comment = DB::table('spad_neighbor_comments')->insertGetId($data + [
            'post_id' => $id, 'author_id' => $user->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->privateResponse(['data' => ['id' => $comment, 'status' => 'pending']]);
    }

    public function deleteComment(Request $request, int $id)
    {
        $user = Neighbors::account($request->user());
        abort_unless(DB::table('spad_neighbor_comments')->where('id', $id)->where('author_id', $user->id)
            ->update(['status' => 'deleted', 'updated_at' => now()]), 404);
        return $this->privateResponse(['status' => true]);
    }

    public function interact(Request $request, int $id, string $kind)
    {
        $user = Neighbors::account($request->user());
        abort_unless(in_array($kind, ['like', 'bookmark'], true), 404);
        $keys = ['post_id' => $id, 'user_id' => $user->id, 'kind' => $kind];
        if ($request->isMethod('DELETE')) {
            DB::table('spad_neighbor_interactions')->where($keys)->delete();
        } else {
            $this->visiblePost($id);
            DB::table('spad_neighbor_interactions')->insertOrIgnore($keys + ['created_at' => now(), 'updated_at' => now()]);
        }
        return $this->privateResponse(['status' => true]);
    }

    public function report(Request $request, string $type, int $id)
    {
        $user = $this->writer($request);
        $row = $this->content($type, $id);
        abort_unless($row && $row->status === 'published', 404);
        if ($type === 'comment') {
            $this->visiblePost($row->post_id);
            abort_unless($this->publishedComments()->where('comment.id', $id)->exists(), 404);
        } else {
            $this->visiblePost($id);
        }
        return $this->submitReview($request, 'reports', $user, $type, $id);
    }

    public function appeal(Request $request, string $type, int $id)
    {
        $user = $this->writer($request);
        $row = $this->content($type, $id);
        abort_unless($row && $row->author_id == $user->id, 404);
        abort_unless(in_array($row->status, ['rejected', 'hidden'], true), 422, 'این محتوا قابل اعتراض نیست.');
        return $this->submitReview($request, 'appeals', $user, $type, $id);
    }

    public function notices(Request $request)
    {
        $user = Neighbors::account($request->user());
        return $this->privateResponse(['data' => DB::table('spad_neighbor_notices')->where('user_id', $user->id)
            ->orderByDesc('id')->limit(60)->get(['id', 'post_id', 'title', 'read_at', 'created_at'])]);
    }

    public function readNotice(Request $request, int $id)
    {
        $user = Neighbors::account($request->user());
        $where = DB::table('spad_neighbor_notices')->where('id', $id)->where('user_id', $user->id);
        abort_unless($where->exists(), 404);
        $where->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
        return $this->privateResponse(['status' => true]);
    }

    public function upload(Request $request, int $id)
    {
        $user = $this->writer($request);
        $this->ownerPost($id, $user);
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        abort_unless(function_exists('imagecreatefromstring'), 503, 'پردازش تصویر در دسترس نیست.');
        $bytes = file_get_contents($request->file('image')->getRealPath());
        $dimensions = getimagesizefromstring($bytes);
        abort_unless($dimensions && $dimensions[0] > 0 && $dimensions[1] > 0 && $dimensions[0] * $dimensions[1] <= 16000000, 422, 'ابعاد تصویر بیش از حد مجاز است.');
        $original = @imagecreatefromstring($bytes);
        abort_unless($original, 422, 'تصویر معتبر نیست.');
        $scale = min(1, 1600 / max($dimensions[0], $dimensions[1]));
        $image = imagescale($original, max(1, (int) round($dimensions[0] * $scale)), max(1, (int) round($dimensions[1] * $scale)));
        imagedestroy($original);
        abort_unless($image, 422, 'آماده‌سازی تصویر انجام نشد.');
        ob_start();
        $encoded = imagejpeg($image, null, 84);
        $bytes = ob_get_clean();
        imagedestroy($image);
        abort_unless($encoded, 422, 'آماده‌سازی تصویر انجام نشد.');
        $path = 'spad-neighbors/'.Str::uuid()->toString().'.jpg';
        abort_unless(Storage::disk('local')->put($path, $bytes), 503, 'ذخیره تصویر انجام نشد.');
        try {
            $mediaId = DB::transaction(function () use ($id, $user, $path) {
                $row = DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->lockForUpdate()->first();
                abort_unless($row && $row->status !== 'deleted', 404);
                abort_if(DB::table('spad_neighbor_media')->where('post_id', $id)->count() >= 3, 422, 'حداکثر سه تصویر مجاز است.');
                DB::table('spad_neighbor_posts')->where('id', $id)->update(['status' => 'pending', 'updated_at' => now()]);
                return DB::table('spad_neighbor_media')->insertGetId(['post_id' => $id, 'path' => $path, 'created_at' => now(), 'updated_at' => now()]);
            }, 3);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return $this->privateResponse(['data' => ['id' => $mediaId, 'status' => 'pending']]);
    }

    public function deleteMedia(Request $request, int $id, int $media)
    {
        $user = Neighbors::account($request->user());
        $this->ownerPost($id, $user);
        $path = DB::transaction(function () use ($id, $media, $user) {
            $post = DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->lockForUpdate()->first();
            abort_unless($post && $post->status !== 'deleted', 404);
            $row = DB::table('spad_neighbor_media')->where('id', $media)->where('post_id', $id)->first();
            abort_unless($row, 404);
            DB::table('spad_neighbor_media')->where('id', $media)->delete();
            DB::table('spad_neighbor_posts')->where('id', $id)->update(['status' => 'pending', 'updated_at' => now()]);
            return $row->path;
        }, 3);
        Storage::disk('local')->delete($path);
        return $this->privateResponse(['status' => true]);
    }

    public function media(Request $request, int $id)
    {
        $row = DB::table('spad_neighbor_media')->where('id', $id)->first();
        abort_unless($row, 404);
        $this->visiblePost((int) $row->post_id);
        return $this->imageResponse($row->path);
    }

    public function ownMedia(Request $request, int $id)
    {
        $user = Neighbors::account($request->user());
        $row = DB::table('spad_neighbor_media')->where('id', $id)->first();
        abort_unless($row, 404);
        $this->ownerPost((int) $row->post_id, $user);
        return $this->imageResponse($row->path);
    }

    private function imageResponse(string $path)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    private function submitReview(Request $request, string $table, User $user, string $type, int $id)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $review = DB::table('spad_neighbor_'.$table);
        $identity = ['user_id' => $user->id, 'target_type' => $type, 'target_id' => $id];
        $inserted = $review->insertOrIgnore($identity + [
            'reason' => $data['reason'],
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (!$inserted) {
            abort_unless($review->where($identity)->where('status', 'open')->exists(), 422, 'این درخواست قبلاً بررسی شده است.');
        }
        return $this->privateResponse(['status' => true]);
    }

    private function content(string $type, int $id)
    {
        abort_unless(in_array($type, ['post', 'comment'], true), 404);
        return DB::table($type === 'post' ? 'spad_neighbor_posts' : 'spad_neighbor_comments')->where('id', $id)->first();
    }

    private function postsPage($query, ?User $viewer)
    {
        $rows = $query->orderByDesc('post.id')->limit(self::PAGE + 1)->get();
        return $this->privateResponse([
            'data' => $this->presentPosts($rows->take(self::PAGE), $viewer)->values(),
            'next_before_id' => $rows->count() > self::PAGE ? $rows[self::PAGE - 1]->id : null,
        ]);
    }

    private function page($rows, callable $present)
    {
        return $this->privateResponse([
            'data' => $rows->take(self::PAGE)->map($present)->values(),
            'next_before_id' => $rows->count() > self::PAGE ? $rows[self::PAGE - 1]->id : null,
        ]);
    }

    private function presentPosts($rows, ?User $viewer)
    {
        $ids = $rows->pluck('id')->all();
        $media = DB::table('spad_neighbor_media')->whereIn('post_id', $ids)->orderBy('id')->get(['id', 'post_id'])->groupBy('post_id');
        $actions = $viewer ? DB::table('spad_neighbor_interactions')->where('user_id', $viewer->id)
            ->whereIn('post_id', $ids)->get(['post_id', 'kind'])->groupBy('post_id') : collect();
        return $rows->map(function ($row) use ($viewer, $media, $actions) {
            $own = $viewer && $row->author_id == $viewer->id;
            return [
                'id' => $row->id, 'title' => $row->title, 'body' => $row->body,
                'category' => $row->category, 'category_label' => Neighbors::CATEGORIES[$row->category] ?? $row->category,
                'region_id' => $row->region_id, 'region' => $row->region_name,
                'author' => $this->displayName($row->first_name), 'resident_verified' => (bool) $row->resident_verified,
                'status' => $row->status, 'lost_status' => $row->lost_status, 'event_at' => $row->event_at,
                'created_at' => $row->created_at, 'published_at' => $row->published_at, 'own' => (bool) $own,
                'likes' => (int) $row->likes, 'comments' => (int) $row->comments,
                'liked' => $actions->get($row->id, collect())->contains('kind', 'like'),
                'bookmarked' => $actions->get($row->id, collect())->contains('kind', 'bookmark'),
                'media' => $media->get($row->id, collect())->map(function ($item) use ($row, $own) {
                    return ['id' => $item->id, 'path' => '/neighbors/'.($own && $row->status !== 'published' ? 'my-media/' : 'media/').$item->id];
                })->values(),
            ];
        });
    }

    private function postQuery()
    {
        return DB::table('spad_neighbor_posts as post')
            ->join('regions as region', 'region.id', '=', 'post.region_id')
            ->join('users as author', 'author.id', '=', 'post.author_id')
            ->leftJoin('spad_neighbor_residencies as residency', function ($join) {
                $join->on('residency.user_id', '=', 'post.author_id')
                    ->on('residency.region_id', '=', 'post.region_id')->where('residency.status', 'verified');
            })
            ->where('region.city_id', SpadLocation::cityId())->where('author.status', User::APPROVED)->whereNull('author.deleted_at')
            ->select(['post.*', 'region.name as region_name', 'author.first_name', 'residency.id as resident_verified'])
            ->selectSub(function ($query) {
                $query->from('spad_neighbor_interactions')->selectRaw('count(*)')->whereColumn('post_id', 'post.id')->where('kind', 'like');
            }, 'likes')->selectSub($this->publishedComments()->selectRaw('count(*)')->whereColumn('comment.post_id', 'post.id'), 'comments');
    }

    private function publishedComments()
    {
        return DB::table('spad_neighbor_comments as comment')
            ->join('users as comment_author', 'comment_author.id', '=', 'comment.author_id')
            ->where('comment.status', 'published')->whereNull('comment_author.deleted_at')
            ->where('comment_author.status', User::APPROVED)
            ->where(function ($query) {
                $query->whereNull('comment.parent_id')->orWhereExists(function ($parent) {
                    $parent->selectRaw('1')->from('spad_neighbor_comments as parent')
                        ->join('users as parent_author', 'parent_author.id', '=', 'parent.author_id')
                        ->whereColumn('parent.id', 'comment.parent_id')->whereColumn('parent.post_id', 'comment.post_id')
                        ->where('parent.status', 'published')->where('parent_author.status', User::APPROVED)
                        ->whereNull('parent_author.deleted_at');
                });
            });
    }

    private function published()
    {
        return $this->postQuery()->where('post.status', 'published');
    }

    private function visiblePost(int $id)
    {
        abort_unless($this->published()->where('post.id', $id)->exists(), 404);
    }

    private function ownerPost(int $id, User $user)
    {
        $row = DB::table('spad_neighbor_posts')->where('id', $id)->where('author_id', $user->id)->where('status', '<>', 'deleted')->first();
        abort_unless($row, 404);
        return $row;
    }

    private function viewer(Request $request): ?User
    {
        if (!$request->bearerToken()) return null;
        $viewer = auth('sanctum')->user();
        return $viewer instanceof User && (int) $viewer->status === User::APPROVED ? $viewer : null;
    }

    private function writer(Request $request): User
    {
        $user = Neighbors::account($request->user());
        abort_unless($user->phone_verified_at, 403, 'برای ارسال محتوا شمارهٔ حساب کاربری باید تأیید شده باشد.');
        return $user;
    }

    private function displayName($name): string
    {
        $name = trim((string) $name);
        return preg_match('/^[\p{L}\p{M} \x{200c}]{2,30}$/u', $name) ? $name : 'همسایهٔ اسپاد';
    }

    private function privateResponse(array $data)
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
