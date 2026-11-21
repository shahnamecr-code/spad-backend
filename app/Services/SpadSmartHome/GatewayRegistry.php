<?php

namespace App\Services\SpadSmartHome;

class GatewayRegistry
{
    public function forOwner(int $userId): array
    {
        $configured = config('spad_smart_home.gateways', []);
        $available = [];
        foreach (is_array($configured) ? $configured : [] as $id => $value) {
            if (!is_string($id) || !preg_match('/\A[a-z][a-z0-9-]{1,49}\z/', $id) || !is_array($value)
                || !isset($value['owner_id']) || !is_int($value['owner_id']) || $value['owner_id'] !== $userId) {
                continue;
            }
            $host = $value['host'] ?? null;
            $ipv4 = $value['ipv4'] ?? null;
            $label = $value['label'] ?? null;
            $lights = $this->lights($value['lights'] ?? null);
            if (!$this->validHost($host) || !$this->publicIpv4($ipv4) || !$this->validLabel($label) || !$lights) {
                continue;
            }

            $available[$id] = [
                'id' => $id, 'label' => $label, 'host' => $host, 'ipv4' => $ipv4, 'lights' => $lights,
                'configuration' => hash('sha256', json_encode([$id, $userId, $host, $ipv4, $lights], JSON_UNESCAPED_UNICODE)),
            ];
        }
        return $available;
    }

    private function lights($value): array
    {
        if (!is_array($value) || count($value) < 1 || count($value) > 4) {
            return [];
        }
        $lights = [];
        foreach ($value as $entity => $settings) {
            if (!is_string($entity) || !preg_match('/\Alight\.[a-z0-9_]{1,80}\z/', $entity) || !is_array($settings)
                || !$this->validLabel($settings['label'] ?? null) || !is_bool($settings['control'] ?? null)) {
                return [];
            }
            $lights[$entity] = ['label' => $settings['label'], 'control' => $settings['control']];
        }
        ksort($lights);
        return $lights;
    }

    private function validLabel($value): bool
    {
        return is_string($value) && preg_match('/\A[^\p{C}]{1,90}\z/u', $value) === 1 && trim($value) !== '';
    }

    private function validHost($host): bool
    {
        return is_string($host) && strlen($host) <= 253
            && preg_match('/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?\z/', $host) === 1;
    }

    private function publicIpv4($ip): bool
    {
        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        [$first, $second, $third] = array_map('intval', explode('.', $ip));
        return $first < 224 && $first !== 0 && $first !== 127
            && !($first === 100 && $second >= 64 && $second <= 127)
            && !($first === 169 && $second === 254)
            && !($first === 192 && $second === 0 && in_array($third, [0, 2], true))
            && !($first === 192 && $second === 88 && $third === 99)
            && !($first === 198 && in_array($second, [18, 19], true))
            && !($first === 198 && $second === 51 && $third === 100)
            && !($first === 203 && $second === 0 && $third === 113);
    }
}
