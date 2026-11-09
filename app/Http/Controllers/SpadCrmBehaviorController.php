<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SpadCrmBehaviorController extends Controller
{
    private const CONSENTS = 'spad_crm_behavior_consents';
    private const EVENTS = 'spad_crm_behavior_events';
    private const TYPES = ['page_view', 'property_view', 'tour_open', 'tour_scene', 'tour_duration', 'favorite'];

    private function subject(Request $request)
    {
        abort_unless($request->user() instanceof \App\Models\User, 403, 'تحلیل رفتار فقط برای حساب کاربری مشتری مجاز است.');
        return (int) $request->user()->id;
    }

    private function ready()
    {
        abort_unless(Schema::hasTable(self::CONSENTS) && Schema::hasTable(self::EVENTS), 503,
            'سیستم تحلیل رفتار هنوز نصب نشده است.');
    }

    public function consent(Request $request)
    {
        $this->ready();
        $uid = $this->subject($request);
        $row = DB::table(self::CONSENTS)->where('user_id', $uid)->first();
        return response()->json([
            'enabled' => $row ? (bool) $row->enabled : false,
            'decided' => (bool) $row,
            'accepted_at' => $row ? $row->accepted_at : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function setConsent(Request $request)
    {
        $this->ready();
        $uid = $this->subject($request);
        $data = $request->validate(['enabled' => 'required|boolean']);
        $enabled = (bool) $data['enabled'];

        DB::transaction(function () use ($uid, $enabled) {
            $row = DB::table(self::CONSENTS)->where('user_id', $uid)->lockForUpdate()->first();
            $values = [
                'enabled' => $enabled,
                'accepted_at' => $enabled ? now() : null,
                'withdrawn_at' => $enabled ? null : now(),
                'updated_at' => now(),
            ];
            if ($row) {
                DB::table(self::CONSENTS)->where('id', $row->id)->update($values);
            } else {
                DB::table(self::CONSENTS)->insert(array_merge($values, [
                    'user_id' => $uid, 'created_at' => now(),
                ]));
            }
            if (!$enabled) {
                DB::table(self::EVENTS)->where('user_id', $uid)->delete();
            }
        });

        return response()->json([
            'enabled' => $enabled,
            'message' => $enabled
                ? 'رضایت تحلیل بازدید ثبت شد.'
                : 'رهگیری غیرفعال شد و سوابق رفتاری حذف شدند.',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function erase(Request $request)
    {
        $request->merge(['enabled' => false]);
        return $this->setConsent($request);
    }

    public function event(Request $request)
    {
        $this->ready();
        $uid = $this->subject($request);
        $data = $request->validate([
            'event_uuid' => 'required|uuid',
            'event_type' => ['required', Rule::in(self::TYPES)],
            'page_path' => 'nullable|string|max:220',
            'property_id' => 'nullable|integer|min:1',
            'tour_id' => 'nullable|integer|min:1',
            'scene_id' => 'nullable|integer|min:1',
            'duration_seconds' => 'nullable|integer|min:1|max:3600',
        ]);

        $path = $data['page_path'] ?? null;
        // Only a pathname is accepted. No search queries, fragments, full URLs,
        // forms, account details or payment routes may enter the event store.
        if ($path !== null) {
            abort_unless((bool) preg_match('~^/[a-zA-Z0-9_/%.-]{0,219}$~D', $path), 422,
                'مسیر صفحه معتبر نیست.');
            // Whitelist known public routes; an authenticated client cannot submit
            // private request IDs or arbitrary personal URLs as page telemetry.
            $publicPath = (bool) preg_match(
                '~^/(?:|property/[0-9]+|ads/[0-9]+|virtual-tour/[A-Za-z0-9_%.-]+|search/?|news(?:/[A-Za-z0-9_%.-]+){0,2}|gold-currency/?|materials-prices/?|construction-tools/?|live-property-price/?|top-builders/?|property-auctions/?|online-renovation/?|about-us/?|contact-us/?|realtors/?|rules/?)$~D',
                $path
            );
            abort_unless($publicPath, 422, 'ثبت رفتار این صفحه مجاز نیست.');
        }

        $type = $data['event_type'];
        $propertyId = $data['property_id'] ?? null;
        $tourId = $data['tour_id'] ?? null;
        $sceneId = $data['scene_id'] ?? null;
        if (in_array($type, ['property_view', 'favorite', 'tour_open', 'tour_scene', 'tour_duration'], true)) {
            abort_unless($propertyId && DB::table('advertisements')->where('id', $propertyId)
                ->where('status', \App\Models\Advertisement::APPROVED)->exists(), 422,
                'کد ملک معتبر نیست.');
        }
        if ($type === 'property_view') {
            abort_unless($path === '/property/'.$propertyId || $path === '/ads/'.$propertyId, 422,
                'مسیر مشاهده با کد ملک سازگار نیست.');
        }
        if (in_array($type, ['tour_open', 'tour_scene', 'tour_duration'], true)) {
            abort_unless($tourId && DB::table('virtual_tours')
                ->where('id', $tourId)->where('advertisement_id', $propertyId)
                ->where('status', 'published')->exists(), 422, 'تور مجازی این ملک معتبر نیست.');
        }
        if ($type === 'tour_scene') {
            abort_unless($sceneId && DB::table('virtual_tour_scenes')
                ->where('id', $sceneId)->where('tour_id', $tourId)->exists(), 422,
                'سکانس تور معتبر نیست.');
        }
        if ($type === 'tour_duration') {
            abort_unless(!empty($data['duration_seconds']), 422, 'مدت بازدید مشخص نیست.');
        }

        // Serialize consent checks with revocation/erasure. No event can be added
        // after a consent withdrawal commits.
        DB::transaction(function () use ($uid, $data, $type, $path, $propertyId, $tourId, $sceneId) {
            $consent = DB::table(self::CONSENTS)->where('user_id', $uid)->lockForUpdate()->first();
            abort_unless($consent && $consent->enabled, 403, 'برای تحلیل رفتار، رضایت کاربر ضروری است.');
            DB::table(self::EVENTS)->insertOrIgnore([
            'event_uuid' => $data['event_uuid'],
            'user_id' => $uid,
            'event_type' => $type,
            'page_path' => $path,
            'property_id' => $propertyId,
            'tour_id' => $tourId,
            'scene_id' => $sceneId,
            'duration_seconds' => $type === 'tour_duration' ? $data['duration_seconds'] : null,
            'created_at' => now(),
            ]);
        });

        return response()->json(['accepted' => true], 202)
            ->header('Cache-Control', 'no-store');
    }
}
