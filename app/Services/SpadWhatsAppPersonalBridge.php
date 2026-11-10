<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SpadWhatsAppPersonalBridge
{
    private const BASE_URL = 'http://127.0.0.1:39317';

    private function bridgeKey(): string
    {
        $filename = '/home/spadesta/spad-staging/spad-wa-personal/bridge.key';
        if (!is_file($filename) || !is_readable($filename)) {
            throw new RuntimeException('سرویس اتصال واتساپ شخصی هنوز روی سرور نصب نشده است.', 503);
        }
        $secret = trim((string) file_get_contents($filename));
        if (!preg_match('/^[a-f0-9]{48,128}$/i', $secret)) {
            throw new RuntimeException('کلید محلی اتصال واتساپ معتبر نیست.', 503);
        }
        return $secret;
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        // Static loopback-only destination. No user-controlled URL or number.
        $secret = $this->bridgeKey();
        try {
            $response = Http::withHeaders([
                'X-SPAD-Self-Key' => $secret,
                'Accept' => 'application/json',
            ])->timeout($path === '/send-self' ? 35 : 5)
                ->send($method, self::BASE_URL . $path, $method === 'POST' ? ['json' => $payload] : []);
            $json = $response->json();
            if (!is_array($json)) throw new RuntimeException('پاسخ سرویس واتساپ معتبر نیست.');
            if (!$response->successful()) {
                throw new RuntimeException('سرویس واتساپ شخصی درخواست را نپذیرفت: ' .
                    mb_substr((string) ($json['error'] ?? $response->status()), 0, 110), 503);
            }
            return $json;
        } catch (Throwable $e) {
            throw new RuntimeException('اتصال داخلی به سرویس واتساپ شخصی برقرار نیست یا پیام پذیرفته نشد.', 503, $e);
        }
    }

    public function status(): array
    {
        try {
            $result = $this->request('GET', '/status');
            return [
                'state' => (string) ($result['state'] ?? 'unavailable'),
                'ready' => (bool) ($result['ready'] ?? false),
                'number_masked' => (string) ($result['linked_number_masked'] ?? ''),
                'pairing_available' => (bool) ($result['pairing_available'] ?? false),
                'service_online' => true,
            ];
        } catch (Throwable $e) {
            return [
                'state' => 'unavailable', 'ready' => false,
                'number_masked' => '', 'pairing_available' => false,
                'service_online' => false,
            ];
        }
    }

    public function pairingQr(): array
    {
        $result = $this->request('GET', '/qr');
        $image = $result['image'] ?? null;
        if ($image !== null && (!is_string($image) || strlen($image) > 300000 ||
            strpos($image, 'data:image/png;base64,') !== 0)) {
            throw new RuntimeException('تصویر QR دریافتی معتبر نیست.', 503);
        }
        return [
            'state' => (string) ($result['state'] ?? 'initializing'),
            'image' => $image,
            'expires_in' => (int) ($result['expires_in'] ?? 0),
        ];
    }

    public function sendSelf(string $png, string $caption): array
    {
        if (strlen($png) < 40000 || strlen($png) > 3000000 ||
            substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('فقط تصاویر PNG معتبر و تولیدشده در اسپاد قابل ارسال هستند.', 422);
        }
        $result = $this->request('POST', '/send-self', [
            'image_base64' => base64_encode($png),
            'caption' => mb_substr($caption, 0, 850),
        ]);
        if (empty($result['accepted'])) {
            throw new RuntimeException('واتساپ پذیرش ارسال به گفت‌وگوی شخصی را تأیید نکرد.', 503);
        }
        return ['accepted' => true, 'message_id' => (string) ($result['message_id'] ?? '')];
    }
}
