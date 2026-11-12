<?php

namespace App\Http\Controllers;

use App\Models\SpadAppDevice;
use App\Models\SpadAppNotification;
use App\Models\User;
use App\Services\SpadMobileAppSettings;
use Illuminate\Http\Request;

/** Endpoints used by the Android/iPhone app itself. */
class SpadMobileAppController extends Controller
{
    private const INBOX_SIZE = 30;
    private const INBOX_DAYS = 60;

    public function config()
    {
        return response()->json(['data' => SpadMobileAppSettings::publicConfig()])
            ->header('Cache-Control', 'no-store');
    }

    /** The app calls this on launch and after sign-in or sign-out. */
    public function registerDevice(Request $request)
    {
        $data = $request->validate([
            'install_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,64}$/'],
            'platform' => ['required', 'string', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z.\-+]+$/'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'notifications_enabled' => ['nullable', 'boolean'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ]);

        $user = $this->appUser($request);
        $device = SpadAppDevice::query()->firstOrNew(['install_id' => $data['install_id']]);
        $device->fill([
            'user_id' => $user ? $user->id : null,
            'platform' => $data['platform'],
            'app_version' => $data['app_version'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'notifications_enabled' => array_key_exists('notifications_enabled', $data)
                ? (bool) $data['notifications_enabled']
                : true,
            'last_seen_at' => now(),
        ]);
        if (array_key_exists('push_token', $data)) {
            $device->push_token = $data['push_token'];
        }
        $device->save();

        return response()->json(['status' => true])->header('Cache-Control', 'no-store');
    }

    /** Public messages for everyone, plus personal ones for the signed-in user. */
    public function notifications(Request $request)
    {
        $data = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!SpadMobileAppSettings::all()['notifications_enabled']) {
            return response()->json(['data' => [], 'latest_id' => 0])->header('Cache-Control', 'no-store');
        }

        $user = $this->appUser($request);
        $query = SpadAppNotification::query()
            ->where('created_at', '>=', now()->subDays(self::INBOX_DAYS))
            ->where(function ($q) use ($user) {
                $q->where('audience', SpadAppNotification::AUDIENCE_ALL);
                if ($user) {
                    $q->orWhere(function ($personal) use ($user) {
                        $personal->where('audience', SpadAppNotification::AUDIENCE_USER)
                            ->where('user_id', $user->id);
                    });
                }
            });
        if (!empty($data['after_id'])) {
            $query->where('id', '>', (int) $data['after_id']);
        }

        $items = $query->orderByDesc('id')->limit(self::INBOX_SIZE)->get();

        return response()->json([
            'data' => $items->map(function (SpadAppNotification $item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'body' => $item->body,
                    'link' => $item->link,
                    'personal' => $item->audience === SpadAppNotification::AUDIENCE_USER,
                    'created_at' => optional($item->created_at)->toIso8601String(),
                ];
            })->values(),
            'latest_id' => (int) ($items->max('id') ?: 0),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** These routes are open to guests; a site user is recognised by their token. */
    private function appUser(Request $request): ?User
    {
        $user = $request->bearerToken() ? auth('sanctum')->user() : null;
        return $user instanceof User ? $user : null;
    }
}
