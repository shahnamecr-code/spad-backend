<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpadCrmBehaviorInsightsController extends Controller
{
    private const EVENTS = 'spad_crm_behavior_events';
    private const CONSENTS = 'spad_crm_behavior_consents';

    private function guard(Request $request)
    {
        abort_unless($request->user() && method_exists($request->user(), 'roles') && $request->user()->roles()
            ->where('name', 'Super Admin')->exists(), 403, 'دسترسی فقط برای مدیر اصلی اسپاد مجاز است.');
        abort_unless(Schema::hasTable(self::EVENTS) && Schema::hasTable(self::CONSENTS),
            503, 'پایگاه داده تحلیل رفتار هنوز نصب نشده است.');
    }

    private function days(Request $request)
    {
        $validated = $request->validate(['days' => 'nullable|integer|in:7,30,90']);
        return (int) ($validated['days'] ?? 30);
    }

    private function propertyRanking($userId, $since)
    {
        return DB::table(self::EVENTS)
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('property_id')
            ->select('property_id')
            ->selectRaw("SUM(CASE event_type WHEN 'property_view' THEN 2 WHEN 'tour_open' THEN 5 WHEN 'tour_scene' THEN 1 WHEN 'favorite' THEN 8 ELSE 0 END) AS interest_score")
            ->selectRaw("SUM(CASE WHEN event_type = 'property_view' THEN 1 ELSE 0 END) AS property_views")
            ->selectRaw("SUM(CASE WHEN event_type = 'tour_open' THEN 1 ELSE 0 END) AS tour_opens")
            ->selectRaw("SUM(CASE WHEN event_type = 'tour_scene' THEN 1 ELSE 0 END) AS scenes_viewed")
            ->selectRaw("COALESCE(SUM(duration_seconds), 0) AS tour_seconds")
            ->selectRaw('MAX(created_at) AS last_seen')
            ->groupBy('property_id')
            ->orderByDesc('interest_score')
            ->limit(15)->get();
    }

    public function overview(Request $request)
    {
        $this->guard($request);
        $days = $this->days($request);
        $since = now()->subDays($days);
        $perPage = (int) ($request->validate(['per_page'=>'nullable|integer|min:5|max:50'])['per_page'] ?? 15);
        $summary = DB::table(self::EVENTS)->where('created_at', '>=', $since)
            ->selectRaw("COUNT(DISTINCT user_id) AS users")
            ->selectRaw("SUM(CASE WHEN event_type = 'property_view' THEN 1 ELSE 0 END) AS property_views")
            ->selectRaw("SUM(CASE WHEN event_type = 'tour_open' THEN 1 ELSE 0 END) AS tour_opens")
            ->selectRaw("COUNT(*) AS total_events")->first();

        $activity = DB::table(self::EVENTS)->where('created_at', '>=', $since)
            ->select('user_id')
            ->selectRaw("MAX(created_at) AS last_seen")
            ->selectRaw("COUNT(*) AS total_events")
            ->selectRaw("COUNT(DISTINCT CASE WHEN property_id IS NOT NULL THEN property_id END) AS properties")
            ->selectRaw("SUM(CASE WHEN event_type = 'tour_open' THEN 1 ELSE 0 END) AS tours")
            ->groupBy('user_id');

        $users = DB::table('users as u')
            ->join(self::CONSENTS.' as c', function($join) {
                $join->on('c.user_id', '=', 'u.id')->where('c.enabled', '=', 1);
            })
            ->joinSub($activity, 'a', 'a.user_id', '=', 'u.id')
            ->leftJoin('spad_crm_contacts as contact', function($join) {
                $join->on('contact.site_user_id', '=', 'u.id')->whereNull('contact.deleted_at');
            })
            ->select('u.id', 'u.first_name', 'u.last_name', 'a.last_seen',
                'a.total_events', 'a.properties', 'a.tours', 'contact.id as contact_id')
            ->orderByDesc('a.last_seen')->paginate($perPage);

        return response()->json([
            'days' => $days,
            'stats' => [
                'consented_users' => DB::table(self::CONSENTS)->where('enabled', true)->count(),
                'active_users' => (int) ($summary->users ?? 0),
                'property_views' => (int) ($summary->property_views ?? 0),
                'tour_opens' => (int) ($summary->tour_opens ?? 0),
                'total_events' => (int) ($summary->total_events ?? 0),
            ],
            'users' => $users->items(), 'total' => $users->total(),
            'last_page' => $users->lastPage(), 'current_page' => $users->currentPage(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function user(Request $request, $userId)
    {
        $this->guard($request);
        $days = $this->days($request);
        $consented = DB::table(self::CONSENTS)->where('user_id', $userId)->where('enabled', true)->exists();
        abort_unless($consented, 404, 'کاربر رضایت فعال برای تحلیل ندارد.');
        $user = DB::table('users')->where('id', $userId)
            ->select('id', 'first_name', 'last_name')->first();
        abort_unless($user, 404);
        $since = now()->subDays($days);

        $events = DB::table(self::EVENTS)->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->select('event_type', 'page_path', 'property_id', 'tour_id', 'scene_id', 'duration_seconds', 'created_at')
            ->orderByDesc('created_at')->limit(80)->get();

        $contactId = DB::table('spad_crm_contacts')->whereNull('deleted_at')
            ->where('site_user_id', $userId)->value('id');

        return response()->json([
            'user' => $user, 'contact_id' => $contactId,
            'days' => $days, 'properties' => $this->propertyRanking($userId, $since),
            'timeline' => $events,
            'interpretation' => 'امتیاز فقط یک نشانه تقریبی از علاقه است، نه تصمیم قطعی مشتری.',
        ])->header('Cache-Control', 'private, no-store');
    }
}
