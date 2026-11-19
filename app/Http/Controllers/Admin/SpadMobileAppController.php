<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SpadAppDevice;
use App\Models\SpadAppNotification;
use App\Models\User;
use App\Services\SpadMobileAppSettings;
use Illuminate\Http\Request;

/** «تنظیمات اپلیکیشن» in the admin panel: app settings and notifications. */
class SpadMobileAppController extends Controller
{
    private const VERSION = 'regex:/^\d{1,4}(\.\d{1,4}){0,3}$/';

    private function requireSuperAdmin(): Admin
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin && (int) $admin->status === Admin::APPROVED
            && $admin->roles()->where('name', 'Super Admin')->where('guard_name', 'admin')->exists(), 403);

        return $admin;
    }

    public function settings()
    {
        $this->requireSuperAdmin();
        return response()->json([
            'data' => SpadMobileAppSettings::all(),
            'stats' => $this->stats(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function saveSettings(Request $request)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'maintenance_enabled' => ['required', 'boolean'],
            'maintenance_message' => ['required', 'string', 'max:300'],
            'latest_version' => ['nullable', 'string', self::VERSION],
            'min_version' => ['nullable', 'string', self::VERSION],
            'update_url' => ['nullable', 'string', 'max:300', $this->linkRule(false)],
            'update_message' => ['nullable', 'string', 'max:300'],
            'announcement_enabled' => ['required', 'boolean'],
            'announcement_text' => ['nullable', 'string', 'max:200'],
            'announcement_link' => ['nullable', 'string', 'max:300', $this->linkRule()],
            'show_market_ticker' => ['required', 'boolean'],
            'show_intro' => ['required', 'boolean'],
            'support_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]*$/'],
            'notifications_enabled' => ['required', 'boolean'],
            'notification_check_minutes' => ['required', 'integer', 'between:15,1440'],
        ]);
        foreach ($data as $key => $value) {
            if ($value === null) {
                $data[$key] = '';
            }
        }
        // Older panel bundles can omit optional fields. Keep their current values.
        $data = array_replace(SpadMobileAppSettings::all(), $data);

        if ($data['latest_version'] !== '' && $data['min_version'] !== ''
            && $this->versionParts($data['min_version']) > $this->versionParts($data['latest_version'])) {
            return response()->json(['message' => 'حداقل نسخه مجاز نمی‌تواند از آخرین نسخه بالاتر باشد.'], 422);
        }
        if ($data['min_version'] !== '' && $data['update_url'] === '') {
            return response()->json(['message' => 'برای به‌روزرسانی اجباری، نشانی دریافت نسخه تازه را هم وارد کنید.'], 422);
        }
        if ($data['announcement_enabled'] && trim($data['announcement_text']) === '') {
            return response()->json(['message' => 'برای نمایش اطلاعیه، متن آن را وارد کنید.'], 422);
        }

        SpadMobileAppSettings::save($data);
        return $this->settings();
    }

    public function notifications()
    {
        $this->requireSuperAdmin();
        $page = SpadAppNotification::withTrashed()->with([
            'user:id,first_name,last_name,mobile',
            'admin:id,first_name,last_name',
            'revoker:id,first_name,last_name',
        ])
            ->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(function (SpadAppNotification $item) {
                return $this->present($item);
            })->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'stats' => $this->stats(),
            'notifications_enabled' => SpadMobileAppSettings::all()['notifications_enabled'],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function sendNotification(Request $request)
    {
        $admin = $this->requireSuperAdmin();
        if (is_string($request->input('mobile'))) {
            $request->merge(['mobile' => $this->normalizedMobile($request->input('mobile'))]);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:600'],
            'link' => ['nullable', 'string', 'max:300', $this->linkRule()],
            'audience' => ['required', 'string', 'in:all,user'],
            'mobile' => ['nullable', 'required_if:audience,user', 'string', 'regex:/^09\d{9}$/'],
        ]);
        if (!SpadMobileAppSettings::all()['notifications_enabled']) {
            return response()->json(['message' => 'برای ارسال اعلان، ابتدا دریافت اعلان را در تنظیمات اپ فعال کنید.'], 422);
        }

        $user = null;
        if ($data['audience'] === SpadAppNotification::AUDIENCE_USER) {
            $recipients = User::query()->where('mobile', $data['mobile'])
                ->where('status', User::APPROVED)->limit(2)->get();
            if ($recipients->isEmpty()) {
                return response()->json(['message' => 'کاربر فعالی با این شماره موبایل پیدا نشد.'], 422);
            }
            if ($recipients->count() > 1) {
                return response()->json(['message' => 'این شماره به بیش از یک حساب فعال مربوط است.'], 422);
            }
            $user = $recipients->first();
        }

        $notification = SpadAppNotification::query()->create([
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'link' => ($data['link'] ?? '') !== '' ? $data['link'] : null,
            'audience' => $data['audience'],
            'user_id' => $user ? $user->id : null,
            'created_by_admin_id' => $admin->id,
        ]);

        return response()->json(['data' => $this->present($notification->load(['user', 'admin']))], 201);
    }

    public function deleteNotification($id)
    {
        $admin = $this->requireSuperAdmin();
        $notification = SpadAppNotification::withTrashed()->findOrFail((int) $id);
        SpadAppNotification::query()->whereKey($notification->id)->update([
            'deleted_at' => now(),
            'revoked_by_admin_id' => $admin->id,
        ]);
        return response()->json(['status' => true]);
    }

    private function versionParts(string $version): array
    {
        return array_pad(array_map('intval', explode('.', $version)), 4, 0);
    }

    private function normalizedMobile(string $mobile): string
    {
        $mobile = strtr(trim($mobile), array_combine(
            preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
        return preg_replace('/\A(?:\+98|0098|98)(?=9\d{9}\z)/', '0', $mobile);
    }

    private function linkRule(bool $allowAppPath = true): \Closure
    {
        return function ($attribute, $value, $fail) use ($allowAppPath) {
            if (!is_string($value)) {
                return;
            }
            $decoded = rawurldecode($value);
            $hasUnsafeCharacters = preg_match('~[\x00-\x1f\x7f\\\\]~', $decoded)
                || preg_match('/\s/', $value);
            if (!$hasUnsafeCharacters && $allowAppPath && strpos($decoded, '//') !== 0
                && preg_match('~\A/(?!/)[A-Za-z0-9._\-/?=&%+#]*\z~', $value)) {
                return;
            }
            $parts = parse_url($value);
            if (!$hasUnsafeCharacters && is_array($parts) && ($parts['scheme'] ?? '') === 'https'
                && !isset($parts['user']) && !isset($parts['pass'])
                && filter_var($parts['host'] ?? '', FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
                return;
            }
            $fail($allowAppPath
                ? 'نشانی باید مسیر معتبر داخل اپ یا پیوند کامل HTTPS باشد.'
                : 'نشانی دریافت نسخه تازه باید یک پیوند کامل HTTPS باشد.');
        };
    }

    private function present(SpadAppNotification $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'body' => $item->body,
            'link' => $item->link,
            'audience' => $item->audience,
            'user' => $item->user ? [
                'name' => trim($item->user->first_name . ' ' . $item->user->last_name),
                'mobile' => $item->user->mobile,
            ] : null,
            'admin' => $item->admin ? trim($item->admin->first_name . ' ' . $item->admin->last_name) : null,
            'created_at' => optional($item->created_at)->toIso8601String(),
            'revoked_at' => optional($item->deleted_at)->toIso8601String(),
            'revoked_by' => $item->revoker ? trim($item->revoker->first_name . ' ' . $item->revoker->last_name) : null,
        ];
    }

    private function stats(): array
    {
        $recent = now()->subDays(30);
        return [
            'devices' => SpadAppDevice::query()->count(),
            'active_30_days' => SpadAppDevice::query()->where('last_seen_at', '>=', $recent)->count(),
            'android' => SpadAppDevice::query()->where('platform', 'android')->count(),
            'ios' => SpadAppDevice::query()->where('platform', 'ios')->count(),
            'signed_in' => SpadAppDevice::query()->whereNotNull('user_id')->count(),
            'notifications_on' => SpadAppDevice::query()->where('notifications_enabled', true)->count(),
        ];
    }
}

