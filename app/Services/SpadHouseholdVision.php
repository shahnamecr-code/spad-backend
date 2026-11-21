<?php

namespace App\Services;

use App\Models\SpadAiAssistantSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

final class SpadHouseholdVision
{
    public function status(): array
    {
        $settings = $this->settings();
        $key = $settings && $settings->enabled ? $settings->apiKey() : null;
        if (!$key) {
            return ['state' => 'not_configured', 'checked_at' => null, 'model' => null];
        }
        return ['model' => $settings->model] + (Cache::get($this->healthCacheKey($key, $settings->model)) ?? [
            'state' => 'configured_unchecked', 'checked_at' => null,
        ]);
    }

    public function request(array $input, string $instructions, string $schemaName, array $schema, int $tokenLimit = 1400): ?array
    {
        $settings = $this->settings();
        $key = $settings && $settings->enabled ? $settings->apiKey() : null;
        if (!$key) {
            return null;
        }
        $cacheKey = $this->healthCacheKey($key, $settings->model);
        if ((Cache::get($cacheKey)['retry_at'] ?? 0) > time()) {
            return null;
        }
        try {
            $response = Http::withToken($key)->acceptJson()->timeout(38)->withOptions(['connect_timeout' => 7])
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $settings->model,
                    'instructions' => $instructions,
                    'input' => [['role' => 'user', 'content' => $input]],
                    'store' => false,
                    'max_output_tokens' => min($tokenLimit, max(120, (int) $settings->max_output_tokens)),
                    'text' => ['format' => ['type' => 'json_schema', 'name' => $schemaName, 'strict' => true, 'schema' => $schema]],
                ]);
            if (!$response->successful()) {
                $status = $response->status();
                $code = $response->json('error.code');
                if (in_array($code, ['credit_balance_exhausted', 'insufficient_quota'], true)) {
                    $this->record($cacheKey, 'quota_exhausted', 600);
                } elseif ($status === 429) {
                    $this->record($cacheKey, 'rate_limited', 60);
                } elseif ($status === 401 || $status === 403) {
                    $this->record($cacheKey, 'auth_error', 120);
                } else {
                    $this->record($cacheKey, 'unavailable', 30);
                }
                return null;
            }
            if ($response->json('status') === 'completed') {
                foreach ((array) $response->json('output') as $item) {
                    if (($item['type'] ?? null) !== 'message') {
                        continue;
                    }
                    foreach ((array) ($item['content'] ?? []) as $entry) {
                        if (($entry['type'] ?? null) === 'output_text' && is_string($entry['text'] ?? null)) {
                            $payload = json_decode($entry['text'], true);
                            if (is_array($payload)) {
                                $this->record($cacheKey, 'responding');
                                return $payload;
                            }
                        }
                    }
                }
            }
            $this->record($cacheKey, 'invalid_response');
        } catch (\Throwable $error) {
            $this->record($cacheKey, 'unavailable', 30);
        }
        return null;
    }

    private function settings(): ?SpadAiAssistantSetting
    {
        return Schema::hasTable('spad_ai_assistant_settings') ? SpadAiAssistantSetting::activeSettings() : null;
    }

    private function healthCacheKey(string $key, string $model): string
    {
        return 'spad-household:vision-status:'.hash_hmac('sha256', $key.':'.$model, (string) config('app.key'));
    }

    private function record(string $key, string $state, int $pause = 0): void
    {
        Cache::put($key, ['state' => $state, 'checked_at' => now()->toISOString(), 'retry_at' => time() + $pause], 86400);
    }
}
