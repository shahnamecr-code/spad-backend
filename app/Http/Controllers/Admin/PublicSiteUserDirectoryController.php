<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\User;
use App\Services\PublicSitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicSiteUserDirectoryController extends Controller
{
    private const VIEWS = 'spad_public_site_page_views';
    private const ACTIONS = PublicSiteUserStatsController::ACTIVITIES + [
        'users' => 'ایجاد حساب اسپاد',
        'meetings' => 'ثبت درخواست بازدید حضوری',
        'advertisements' => 'ثبت آگهی ملک',
    ];
    private const USER_COLUMNS = [
        'id', 'first_name', 'last_name', 'mobile', 'email', 'status', 'created_at',
        'updated_at', 'deleted_at', 'phone_verified_at', 'email_verified_at',
    ];

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);
        $filters = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'q' => 'nullable|string|max:100',
            'registered' => 'sometimes|in:all,today,week,month',
            'status' => 'sometimes|in:all,active,inactive,archived',
            'sort' => 'sometimes|in:newest,oldest',
        ]);

        $today = now('Asia/Tehran')->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $hasViews = Schema::hasTable(self::VIEWS);
        $accounts = User::withTrashed();
        $query = (clone $accounts)->select(self::USER_COLUMNS);
        $term = trim(strtr($filters['q'] ?? '', [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
        if ($term !== '') {
            foreach (preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $like = '%' . strtr($word, ['!' => '!!', '_' => '!_', '%' => '!%']) . '%';
                $query->where(function ($match) use ($word, $like) {
                    foreach (['first_name', 'last_name', 'email', 'mobile'] as $column) {
                        $match->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$like]);
                    }
                    if (ctype_digit($word) && strlen($word) <= 16) {
                        $match->orWhere('id', $word);
                    }
                });
            }
        }

        $days = ['today' => 0, 'week' => 6, 'month' => 29][$filters['registered'] ?? 'all'] ?? null;
        if ($days !== null) {
            $query->where('created_at', '>=', $today->copy()->subDays($days))->where('created_at', '<', $tomorrow);
        }

        $status = $filters['status'] ?? 'all';
        if ($status === 'archived') {
            $query->whereNotNull('deleted_at');
        } elseif ($status !== 'all') {
            $query->whereNull('deleted_at')->where('status', $status === 'active' ? User::APPROVED : User::REJECTED);
        }

        if ($hasViews) {
            $query->addSelect(['last_view_at' => DB::table(self::VIEWS)->select('created_at')
                ->whereColumn('user_id', 'users.id')->orderByDesc('created_at')->limit(1)]);
        }
        $direction = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';
        $users = $query->orderBy('created_at', $direction)->orderBy('id', $direction)->paginate(25);

        return $this->report([
            'summary' => [
                'accounts' => $accounts->count(),
                'new_accounts' => (clone $accounts)->where('created_at', '>=', $today)->where('created_at', '<', $tomorrow)->count(),
                'visitors' => Schema::hasTable('spad_public_site_visitors')
                    ? DB::table('spad_public_site_visitors')->where('visit_day', $today->toDateString())->count()
                    : null,
                'signed_in_visitors' => $hasViews ? DB::table(self::VIEWS)->where('created_at', '>=', $today)
                    ->where('created_at', '<', $tomorrow)->distinct()->count('user_id') : null,
            ],
            'users' => $users->getCollection()->map(fn (User $user) => $this->account($user)),
            'page' => $users->currentPage(),
            'pages' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    public function show(Request $request, string $userId)
    {
        $this->authorizeSuperAdmin($request);
        abort_if(strlen($userId) > 20, 404);
        $filters = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'kind' => 'sometimes|in:all,page,property,operation',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : 'sometimes'],
        ]);

        $user = User::withTrashed()->findOrFail($userId, self::USER_COLUMNS);
        $hasViews = Schema::hasTable(self::VIEWS);
        $views = $hasViews ? DB::table(self::VIEWS)->where('user_id', $user->id) : null;
        $totals = $views ? (clone $views)->selectRaw(
            'COUNT(*) AS views, COUNT(property_id) AS property_views, COUNT(DISTINCT property_id) AS properties, MAX(created_at) AS last_view_at'
        )->first() : null;
        $user->last_view_at = $totals->last_view_at ?? null;

        $kind = $filters['kind'] ?? 'all';
        $from = isset($filters['from']) ? Carbon::parse($filters['from'], 'Asia/Tehran')->startOfDay() : null;
        $until = isset($filters['to']) ? Carbon::parse($filters['to'], 'Asia/Tehran')->startOfDay()->addDay() : null;
        $restrictDates = static function ($query) use ($from, $until) {
            return $query->whereNotNull('created_at')
                ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
                ->when($until, fn ($builder) => $builder->where('created_at', '<', $until));
        };

        $timeline = null;
        if ($views && $kind !== 'operation') {
            $timeline = $restrictDates($views)->select('id', 'created_at', 'page', 'property_id')
                ->selectRaw("'view' AS source");
            if ($kind === 'page') {
                $timeline->whereNull('property_id');
            } elseif ($kind === 'property') {
                $timeline->whereNotNull('property_id');
            }
        }
        if ($kind === 'all' || $kind === 'operation') {
            foreach (array_keys(self::ACTIONS) as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $records = DB::table($table);
                if ($table === 'advertisements') {
                    $records->where('writable_type', $user->getMorphClass())->where('writable_id', $user->id);
                } else {
                    $records->where($table === 'users' ? 'id' : 'user_id', $user->id);
                }
                $operation = $restrictDates($records)
                    ->select('id', 'created_at')->selectRaw('NULL AS page, NULL AS property_id, ? AS source', [$table]);
                $timeline = $timeline ? $timeline->unionAll($operation) : $operation;
            }
        }

        $activity = $timeline ? DB::query()->fromSub($timeline, 'activity')
            ->orderByDesc('created_at')->orderBy('source')->orderByDesc('id')->paginate(30) : null;
        $events = $activity ? $activity->getCollection() : collect();
        $propertyIds = $events->pluck('property_id')->filter()->unique()->values();
        $properties = $propertyIds->isEmpty() ? collect() : Advertisement::query()
            ->whereKey($propertyIds->all())->with(['properties' => fn ($query) => $query->where('property_slug', 'title')])
            ->get(['id', 'status'])->keyBy('id');

        return $this->report([
            'user' => $this->account($user),
            'page_tracking_available' => $hasViews,
            'totals' => [
                'page_views' => $totals ? (int) $totals->views - (int) $totals->property_views : null,
                'property_views' => $totals ? (int) $totals->property_views : null,
                'properties' => $totals ? (int) $totals->properties : null,
            ],
            'events' => $events->map(static function ($event) use ($properties) {
                $property = $properties->get($event->property_id);
                $title = $property ? $property->properties->first() : null;
                return [
                    'id' => $event->source . ':' . $event->id,
                    'kind' => $event->property_id ? 'property' : ($event->source === 'view' ? 'page' : 'operation'),
                    'label' => $event->source === 'view' ? (PublicSitePages::LABELS[$event->page] ?? $event->page) : self::ACTIONS[$event->source],
                    'at' => Carbon::parse($event->created_at, 'Asia/Tehran')->toIso8601String(),
                    'page' => $event->page,
                    'property_id' => $event->property_id,
                    'property_title' => $title ? ($title->text_value ?: $title->textarea_value) : null,
                    'property_url' => $property && (int) $property->status === Advertisement::APPROVED ? '/property/' . $property->id : null,
                ];
            }),
            'page' => $activity ? $activity->currentPage() : 1,
            'pages' => $activity ? $activity->lastPage() : 1,
            'total' => $activity ? $activity->total() : 0,
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        $admin = $request->user() instanceof Admin ? $request->user() : auth('admin')->user();
        abort_unless($admin instanceof Admin && $admin->hasRole('Super Admin'), 403);
    }

    private function account(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => trim($user->full_name),
            'mobile' => $user->mobile,
            'email' => $user->email,
            'status' => $user->trashed() ? 'archived' : ((int) $user->status === User::APPROVED ? 'active' : 'inactive'),
            'registered_at' => optional($user->created_at)->toIso8601String(),
            'updated_at' => optional($user->updated_at)->toIso8601String(),
            'archived_at' => optional($user->deleted_at)->toIso8601String(),
            'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
            'phone_verified_at' => $this->timestamp($user->phone_verified_at),
            'last_view_at' => $this->timestamp($user->last_view_at),
        ];
    }

    private function timestamp($value): ?string
    {
        return $value ? Carbon::parse($value, 'Asia/Tehran')->toIso8601String() : null;
    }

    private function report(array $data)
    {
        return response()->json(['data' => $data])->header('Cache-Control', 'private, no-store');
    }
}
