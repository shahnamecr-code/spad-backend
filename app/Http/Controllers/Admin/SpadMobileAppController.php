<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpadAppDevice;
use App\Models\SpadAppNotification;
use App\Models\User;
use App\Services\SpadMobileAppSettings;
use Illuminate\Http\Request;

/** «تنظیمات اپلیکیشن» in the admin panel: app settings and notifications. */
class SpadMobileAppController extends Controller
{
    private const VERSION = 'regex:/^\d{1,4}(\.\d{1,4}){0,3}$/';
    // A screen inside the app ("/search") or a full https address.
    private const LINK = 'regex:/^(\/[A-Za-z0-9\-_\/?=&%.]*|https:\/\/[^\s]+)$/';

    private function requireSuperAdmin()
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
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
            'update_url' => ['nullable', 'string', 'max:300', 'regex:/^https:\/\/[^\s]+$/'],
            'update_message' => ['nullable', 'string', 'max:300'],
            'announcement_enabled' => ['required', 'boolean'],
            'announcement_text' => ['nullable', 'string', 'max:200'],
            'announcement_link' => ['nullable', 'string', 'max:300', self::LINK],
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

        if ($data['latest_version'] !== '' && $data['min_version'] !== ''
            && version_compare($data['min_version'], $data['latest_version'], '>')) {
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
        $page = SpadAppNotification::query()->with(['user:id,first_name,last_name,mobile', 'admin:id,first_name,last_name'])
            ->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(function (SpadAppNotification $item) {
                return $this->present($item);
            })->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'stats' => $this->stats(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function sendNotification(Request $request)
    {
        $admin = $this->requireSuperAdmin();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:600'],
            'link' => ['nullable', 'string', 'max:300', self::LINK],
            'audience' => ['required', 'string', 'in:all,user'],
            'mobile' => ['nullable', 'required_if:audience,user', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $user = null;
        if ($data['audience'] === SpadAppNotification::AUDIENCE_USER) {
            $user = User::query()->where('mobile', $data['mobile'])->first();
            if (!$user) {
                return response()->json(['message' => 'کاربری با این شماره موبایل پیدا نشد.'], 422);
            }
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
        $this->requireSuperAdmin();
        SpadAppNotification::query()->findOrFail((int) $id)->delete();
        return response()->json(['status' => true]);
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
