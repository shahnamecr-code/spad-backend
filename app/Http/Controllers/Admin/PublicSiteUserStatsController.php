<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicSiteUserStatsController extends Controller
{
    private const ACTIVITIES = [
        'mortgage_requests' => 'ثبت درخواست وام مسکن',
        'spad_property_affair_requests' => 'ثبت درخواست امور ملکی',
        'inquiry_requests' => 'ثبت استعلام ملکی',
        'spad_property_acceptance_requests' => 'ثبت درخواست پذیرش ملک',
        'spad_user_property_assets' => 'افزودن ملک به سبد املاک',
        'spad_renovation_projects' => 'ساخت پروژهٔ بازسازی',
    ];

    public function index(Request $request)
    {
        $admin = $request->user();
        if (!$admin instanceof Admin) {
            $admin = auth('admin')->user();
        }
        abort_unless($admin instanceof Admin && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
        $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:10000']]);

        $start = now('Asia/Tehran')->startOfDay();
        $end = $start->copy()->addDay();
        $users = User::query()->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25, ['id', 'first_name', 'last_name', 'mobile', 'created_at']);
        $ids = $users->getCollection()->pluck('id')->all();
        $actions = [];

        if ($ids) {
            foreach (self::ACTIVITIES as $table => $label) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $rows = DB::table($table)->whereIn('user_id', $ids)
                    ->where('created_at', '>=', $start)->where('created_at', '<', $end)
                    ->selectRaw('user_id, COUNT(*) AS total, MAX(created_at) AS latest_at')
                    ->groupBy('user_id')->get();
                foreach ($rows as $row) {
                    $actions[$row->user_id][] = [
                        'label' => $label,
                        'count' => (int) $row->total,
                        'latest_at' => \Illuminate\Support\Carbon::parse($row->latest_at)->toIso8601String(),
                    ];
                }
            }
        }

        $visits = Schema::hasTable('spad_public_site_visitors')
            ? DB::table('spad_public_site_visitors')->where('visit_day', $start->toDateString())->count()
            : null;

        return response()->json(['data' => [
            'day' => $start->toDateString(),
            'visitors' => $visits,
            'new_accounts' => $users->total(),
            'page' => $users->currentPage(),
            'pages' => $users->lastPage(),
            'users' => $users->getCollection()->map(static function (User $user) use ($actions): array {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'mobile' => $user->mobile,
                    'registered_at' => $user->created_at->toIso8601String(),
                    'actions' => $actions[$user->id] ?? [],
                ];
            })->values(),
        ]])->header('Cache-Control', 'private, no-store');
    }
}
