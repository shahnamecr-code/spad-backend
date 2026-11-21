<?php

namespace App\Services\SpadSmartHome;

class HomeAssistant
{
    public function authenticate(array $gateway, string $credential): bool
    {
        $reply = $this->call($gateway, $credential, '/api/');
        return $reply['status'] === 200 && ($reply['data']['message'] ?? null) === 'API running.';
    }

    public function readLight(array $gateway, string $credential, string $entity): array
    {
        if (!isset($gateway['lights'][$entity])) {
            return ['state' => null, 'observed_at' => null];
        }
        $reply = $this->call($gateway, $credential, '/api/states/' . $entity);
        $data = $reply['data'];
        if ($reply['status'] !== 200 || ($data['entity_id'] ?? null) !== $entity
            || !in_array($data['state'] ?? null, ['on', 'off', 'unavailable', 'unknown'], true)) {
            return ['state' => null, 'observed_at' => null];
        }
        return ['state' => $data['state'], 'observed_at' => now()->utc()->toIso8601String()];
    }

    public function commandLight(array $gateway, string $credential, string $entity, string $state): bool
    {
        if (empty($gateway['lights'][$entity]['control']) || !in_array($state, ['on', 'off'], true)) {
            return false;
        }
        $reply = $this->call($gateway, $credential, '/api/services/light/turn_' . $state, ['entity_id' => $entity]);
        return $reply['status'] === 200;
    }

    protected function call(array $gateway, string $credential, string $path, ?array $body = null): array
    {
        $failed = ['status' => 0, 'data' => []];
        if (!function_exists('curl_init')) {
            return $failed;
        }
        $request = curl_init('https://' . $gateway['host'] . $path);
        if (!$request) {
            return $failed;
        }
        $payload = '';
        $options = [
            CURLOPT_RESOLVE => [$gateway['host'] . ':443:' . $gateway['ipv4']],
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROXY => '',
            CURLOPT_CONNECTTIMEOUT_MS => 1300,
            CURLOPT_TIMEOUT_MS => 2300,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $credential, 'Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$payload): int {
                if (strlen($payload) + strlen($chunk) > 65536) {
                    return 0;
                }
                $payload .= $chunk;
                return strlen($chunk);
            },
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[constant('CURLOPT_PROTOCOLS_STR')] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if ($body !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        try {
            if (!curl_setopt_array($request, $options) || curl_exec($request) === false) {
                return $failed;
            }
            $data = json_decode($payload, true);
            return ['status' => (int) curl_getinfo($request, CURLINFO_HTTP_CODE), 'data' => is_array($data) ? $data : []];
        } finally {
            if (is_resource($request)) {
                curl_close($request);
            }
        }
    }
}
