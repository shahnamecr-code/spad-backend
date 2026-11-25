<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SpadAgentControlController extends Controller
{
    private const ENDPOINT = 'https://agent.spad.estate/api/v1/control/overview';
    private const PATH = '/api/v1/control/overview';
    private const PROVIDERS = ['payments', 'registry', 'sms', 'ai', 'bills', 'social'];

    public function index(Request $request)
    {
        $admin = $request->user();
        abort_unless($admin && $admin->roles()->where('name', 'Super Admin')->exists(), 403,
            'مدیریت پنل مشاورین املاک فقط برای مدیر اصلی مجاز است.');

        $key = config('services.spad_agent.control_key');
        if (!is_string($key) || strlen($key) < 32) {
            return $this->status('requires_configuration');
        }

        $time = (string) now()->timestamp;
        $nonce = bin2hex(random_bytes(16));
        $canonical = implode("\n", ['GET', self::PATH, $time, $nonce, hash('sha256', '')]);
        try {
            $reply = Http::acceptJson()->withHeaders([
                'X-Agent-Timestamp' => $time,
                'X-Agent-Nonce' => $nonce,
                'X-Agent-Signature' => hash_hmac('sha256', $canonical, $key),
            ])->withOptions(['connect_timeout' => 2, 'allow_redirects' => false])->timeout(4)->get(self::ENDPOINT);
            $data = $reply->json('data');
            if ($reply->status() !== 200 || !is_array($data) || !is_array($data['offices_by_status'] ?? null) || !is_array($data['providers'] ?? null)) {
                return $this->status('unavailable');
            }
        } catch (Throwable $error) {
            return $this->status('unavailable');
        }

        $offices = [];
        foreach (['trial', 'active', 'suspended'] as $status) {
            $value = filter_var($data['offices_by_status'][$status] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($value !== false) $offices[$status] = $value;
        }
        $providers = [];
        foreach ($data['providers'] as $row) {
            if (is_array($row) && in_array($row['id'] ?? null, self::PROVIDERS, true) && in_array($row['status'] ?? null, ['blocked','configured','verified'], true)) {
                $providers[$row['id']] = $row['status'];
            }
        }

        Log::info('spad_agent.control.overview', ['admin_id' => (int) $admin->getKey()]);
        return $this->status('connected', ['offices' => $offices, 'providers' => $providers]);
    }

    private function status(string $status, array $data = [])
    {
        return response()->json(['data' => ['status' => $status] + $data])->header('Cache-Control', 'private, no-store');
    }
}
