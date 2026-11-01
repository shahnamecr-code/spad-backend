<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketPriceService
{
    private const DEFAULT_API_URL = 'https://api.navasan.tech/latest/';
    private const DEFAULT_REFRESH_SECONDS = 7200;

    private $settingsCache = null;

    /**
     * Return public ticker data. The provider API key always stays server-side.
     */
    public function getTickerData(): array
    {
        $enabled = $this->isEnabled('market_ticker_enabled', true);
        $refreshSeconds = $this->refreshSeconds();

        if (!$enabled) {
            return [
                'enabled' => false,
                'configured' => false,
                'refresh_seconds' => $refreshSeconds,
                'source_status' => 'disabled',
                'items' => [],
            ];
        }

        $apiKey = trim((string) $this->setting('market_ticker_api_key', ''));
        $apiUrl = trim((string) $this->setting('market_ticker_api_url', self::DEFAULT_API_URL));
        if ($apiKey === '') {
            return [
                'enabled' => true,
                'configured' => false,
                'refresh_seconds' => $refreshSeconds,
                'source_status' => 'not_configured',
                'items' => [],
            ];
        }

        // All browsers share ONE server-side cache; no upstream calls per ticker poll.
        // Importantly, a provider 429 must never cause another request on every
        // frontend refresh. Cache the failure and honour Retry-After.
        $cacheSuffix = sha1($apiUrl . '|' . $apiKey);
        $cacheKey = 'market_ticker:prices:' . $cacheSuffix;
        $lastGoodKey = $cacheKey . ':last_good';
        $backoffKey = $cacheKey . ':provider_backoff';
        $mutexKey = $cacheKey . ':refresh_mutex';
        $items = Cache::get($cacheKey);
        $sourceStatus = Cache::get($backoffKey) ?: 'cached';

        if (!is_array($items)) {
            $lastGood = Cache::get($lastGoodKey, []);
            $lastGood = is_array($lastGood) ? $lastGood : [];

            if (Cache::get($backoffKey)) {
                $items = $lastGood;
            } elseif (!Cache::add($mutexKey, 1, 45)) {
                // An in-flight refresh owns the API call.
                $items = $lastGood;
                $sourceStatus = 'refreshing';
            } else {
                $cooldownSeconds = 900;
                try {
                    // No automatic retries: each 429 would otherwise spend more quota.
                    $response = Http::acceptJson()
                        ->timeout(12)
                        ->get($apiUrl, ['api_key' => $apiKey]);

                    if ($response->status() === 429) {
                        $retryAfter = $response->header('Retry-After');
                        $retrySeconds = is_scalar($retryAfter) && ctype_digit((string) $retryAfter)
                            ? (int) $retryAfter : 900;
                        $cooldownSeconds = max(300, min(7200, $retrySeconds));
                        $sourceStatus = 'rate_limited';
                        throw new \RuntimeException('Market quote provider rate limited (HTTP 429).');
                    }
                    if (!$response->successful()) {
                        $sourceStatus = 'unavailable';
                        throw new \RuntimeException('Market quote provider HTTP ' . $response->status());
                    }

                    $payload = $response->json();
                    if (!is_array($payload)) {
                        $sourceStatus = 'invalid_response';
                        throw new \RuntimeException('Invalid market quote response.');
                    }

                    $normalized = $this->normalize($payload);
                    if (count($normalized) === 0) {
                        $sourceStatus = 'invalid_response';
                        throw new \RuntimeException('Market quote response contains no usable prices.');
                    }

                    $items = $normalized;
                    Cache::put($cacheKey, $items, $refreshSeconds);
                    Cache::forever($lastGoodKey, $items);
                    Cache::forget($backoffKey);
                    $sourceStatus = 'live';
                } catch (Throwable $e) {
                    // Never replace the last valid prices with zeroes or placeholders.
                    $items = $lastGood;
                    Cache::put($backoffKey, $sourceStatus === 'cached' ? 'unavailable' : $sourceStatus, $cooldownSeconds);
                    Cache::put($cacheKey, $items, min(900, $cooldownSeconds));
                    Log::warning('Market quote upstream unavailable.', [
                        'type' => get_class($e),
                        'status' => $sourceStatus,
                    ]);
                } finally {
                    Cache::forget($mutexKey);
                }
            }
        }

        return [
            'enabled' => true,
            'configured' => true,
            'refresh_seconds' => $refreshSeconds,
            'source_status' => $sourceStatus,
            'items' => array_values($items ?: []),
        ];
    }

    private function normalize(array $payload): array
    {
        $definitions = [
            [
                'key' => 'usd',
                'label' => 'دلار',
                'setting' => 'market_ticker_usd',
                'sources' => ['usd', 'usd_sell'],
            ],
            [
                'key' => 'gold_18',
                'label' => 'طلای ۱۸ عیار',
                'setting' => 'market_ticker_gold_18',
                'sources' => ['18ayar'],
            ],
            [
                'key' => 'gold_24',
                'label' => 'طلای ۲۴ عیار',
                'setting' => 'market_ticker_gold_24',
                'sources' => ['24ayar'],
                'derive' => ['source' => '18ayar', 'factor' => 4 / 3],
            ],
            [
                'key' => 'new_coin',
                'label' => 'سکه جدید',
                'setting' => 'market_ticker_new_coin',
                'sources' => ['sekkeh'],
            ],
            [
                'key' => 'old_coin',
                'label' => 'سکه قدیم',
                'setting' => 'market_ticker_old_coin',
                'sources' => ['bahar'],
            ],
            [
                'key' => 'half_coin',
                'label' => 'نیم سکه',
                'setting' => 'market_ticker_half_coin',
                'sources' => ['nim'],
            ],
            [
                'key' => 'quarter_coin',
                'label' => 'ربع سکه',
                'setting' => 'market_ticker_quarter_coin',
                'sources' => ['rob'],
            ],
            [
                'key' => 'gold_ounce',
                'label' => 'انس طلا',
                'setting' => 'market_ticker_gold_ounce',
                'sources' => ['usd_xau', 'xau'],
            ],
            [
                'key' => 'melted_gold',
                'label' => 'آبشده نقدی',
                'setting' => 'market_ticker_melted_gold',
                'sources' => ['abshodeh'],
            ],
            [
                'key' => 'mesghal_gold',
                'label' => 'مثقال طلا',
                'setting' => 'market_ticker_mesghal_gold',
                'sources' => ['mesghal', 'mesghal_tala'],
                // Fallback when the provider does not expose a dedicated mesghal symbol.
                'derive' => ['source' => '18ayar', 'factor' => 4.3318],
            ],
            [
                'key' => 'eur',
                'label' => 'یورو',
                'setting' => 'market_ticker_eur',
                'sources' => ['eur'],
            ],
            [
                'key' => 'gbp',
                'label' => 'پوند انگلیس',
                'setting' => 'market_ticker_gbp',
                'sources' => ['gbp'],
            ],
            [
                'key' => 'usdt',
                'label' => 'تتر',
                'setting' => 'market_ticker_usdt',
                'sources' => ['usdt'],
            ],
            [
                'key' => 'aed',
                'label' => 'درهم امارات',
                'setting' => 'market_ticker_aed',
                'sources' => ['aed', 'aed_sell', 'dirham_dubai'],
            ],
            [
                'key' => 'try',
                'label' => 'لیر ترکیه',
                'setting' => 'market_ticker_try',
                'sources' => ['try', 'try_hav'],
            ],
        ];

        $items = [];

        foreach ($definitions as $definition) {
            if (!$this->isEnabled($definition['setting'], true)) {
                continue;
            }

            $data = isset($definition['sources'])
                ? $this->extract($payload, $definition['sources'])
                : null;

            if (!$data && isset($definition['derive'])) {
                $source = $this->extract($payload, [$definition['derive']['source']]);
                if ($source) {
                    $data = [
                        'value' => round($source['value'] * $definition['derive']['factor']),
                        'change' => isset($source['change']) && is_numeric($source['change'])
                            ? round(((float) $source['change']) * $definition['derive']['factor'])
                            : null,
                        'timestamp' => $source['timestamp'] ?? null,
                    ];
                }
            }

            if (!$data || !isset($data['value']) || !is_numeric($data['value'])) {
                continue;
            }

            $items[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'value' => (float) $data['value'],
                'change' => isset($data['change']) && is_numeric($data['change'])
                    ? (float) $data['change']
                    : null,
                'timestamp' => $data['timestamp'] ?? null,
            ];
        }

        return $items;
    }

    private function extract(array $payload, array $sources): ?array
    {
        foreach ($sources as $source) {
            $row = $payload[$source] ?? null;
            if (!is_array($row) || !array_key_exists('value', $row)) {
                continue;
            }

            $value = $this->numericValue($row['value']);
            if ($value === null) {
                continue;
            }

            return [
                'value' => $value,
                'change' => isset($row['change']) ? $this->numericValue($row['change']) : null,
                'timestamp' => $row['timestamp'] ?? null,
            ];
        }

        return null;
    }

    private function numericValue($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace([',', '٬', ' '], '', trim($value));
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        return $normalized !== '' && is_numeric($normalized) ? (float) $normalized : null;
    }

    private function refreshSeconds(): int
    {
        $value = (int) $this->setting('market_ticker_refresh_seconds', self::DEFAULT_REFRESH_SECONDS);
        return max(30, min($value ?: self::DEFAULT_REFRESH_SECONDS, 86400));
    }

    private function isEnabled(string $code, bool $default = true): bool
    {
        $value = $this->setting($code, $default ? '1' : '0');
        return !in_array((string) $value, ['0', 'false', 'off', 'no', ''], true);
    }

    private function setting(string $code, $default = null)
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = [];
            $settings = Setting::query()->where('group', 'market_ticker')->get();
            foreach ($settings as $setting) {
                $this->settingsCache[$setting->code] = $setting->value;
            }
        }

        return array_key_exists($code, $this->settingsCache)
            ? $this->settingsCache[$code]
            : $default;
    }
}
