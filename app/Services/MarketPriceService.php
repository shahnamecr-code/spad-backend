<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Public Iranian market prices. No API key, visitor-side provider access or
 * extrapolated prices. All quote values are in toman except the USD gold ounce.
 *
 * The existing gold-currency page expects coin values in thousands of toman
 * and multiplies by 1000. Keep that compatibility contract until the frontend
 * can be upgraded independently; other values are already in toman.
 */
class MarketPriceService
{
    private const URLS = [
        'https://raw.githubusercontent.com/iran-market/iran-market.github.io/main/data/latest-toman.json',
        'https://raw.githubusercontent.com/iran-market/iran-market.github.io/main/data/popular.json',
    ];
    private const CACHE_PREFIX = 'market_ticker:open_market_v1';
    private const CACHE_SECONDS = 600;
    private const LAST_GOOD_SECONDS = 172800;
    private const MAX_QUOTE_AGE_SECONDS = 7200;

    private $settingsCache = null;

    public function getTickerData(): array
    {
        $enabled = $this->isEnabled('market_ticker_enabled', true);
        $refresh = $this->refreshSeconds();
        if (!$enabled) {
            return ['enabled' => false, 'configured' => true, 'refresh_seconds' => $refresh,
                'source_status' => 'disabled', 'items' => []];
        }

        $snapshot = Cache::get(self::CACHE_PREFIX . ':snapshot');
        if (is_array($snapshot) && isset($snapshot['items']) && is_array($snapshot['items'])) {
            return $this->reply($snapshot['items'], $refresh, $snapshot['status'] ?? 'cached');
        }

        $lastGood = Cache::get(self::CACHE_PREFIX . ':last_good', []);
        $lastGood = is_array($lastGood) ? $lastGood : [];

        if (Cache::get(self::CACHE_PREFIX . ':backoff')) {
            return $this->reply($lastGood, $refresh, 'unavailable');
        }

        if (!Cache::add(self::CACHE_PREFIX . ':mutex', 1, 45)) {
            return $this->reply($lastGood, $refresh, 'refreshing');
        }

        try {
            $items = [];
            foreach (self::URLS as $url) {
                try {
                    $response = Http::acceptJson()->timeout(15)->get($url);
                    if ($response->status() === 429) {
                        throw new \RuntimeException('Upstream rate limit');
                    }
                    if (!$response->successful()) {
                        throw new \RuntimeException('Upstream HTTP ' . $response->status());
                    }
                    $payload = $response->json();
                    if (!is_array($payload)) {
                        throw new \RuntimeException('Invalid upstream JSON');
                    }
                    $items = $this->normalizeOpenMarket($payload);
                    // Require actual USD and gold quotes before treating a response as successful.
                    $keys = array_column($items, 'key');
                    if (in_array('usd', $keys, true) && in_array('gold_18', $keys, true)) {
                        break;
                    }
                    $items = [];
                } catch (Throwable $error) {
                    Log::warning('Open market upstream failed.', [
                        'source' => parse_url($url, PHP_URL_HOST),
                        'exception_type' => get_class($error),
                    ]);
                }
            }

            if (count($items) === 0) {
                Cache::put(self::CACHE_PREFIX . ':backoff', 1, 300);
                $this->cacheSnapshot($lastGood, 'unavailable', 300);
                return $this->reply($lastGood, $refresh, 'unavailable');
            }

            Cache::put(self::CACHE_PREFIX . ':last_good', $items, self::LAST_GOOD_SECONDS);
            Cache::forget(self::CACHE_PREFIX . ':backoff');
            $this->cacheSnapshot($items, 'live', $refresh);
            return $this->reply($items, $refresh, 'live');
        } finally {
            Cache::forget(self::CACHE_PREFIX . ':mutex');
        }
    }

    private function cacheSnapshot(array $items, string $status, int $seconds): void
    {
        Cache::put(self::CACHE_PREFIX . ':snapshot',
            ['items' => $items, 'status' => $status], $seconds);
    }

    private function reply(array $items, int $refresh, string $status): array
    {
        return [
            'enabled' => true,
            'configured' => true, // No key is needed for the public feed.
            'refresh_seconds' => $refresh,
            'source_status' => $status,
            'provider' => 'Iran Market Data',
            'items' => array_values($items),
        ];
    }

    private function normalizeOpenMarket(array $payload): array
    {
        $groups = $payload['data']['categories'] ?? null;
        if (!is_array($groups)) {
            $groups = ['popular' => $payload['data'] ?? []];
        }

        $bySymbol = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group as $row) {
                if (is_array($row) && isset($row['symbol'])) {
                    $bySymbol[$row['symbol']] = $row;
                }
            }
        }

        $definitions = [
            'usd' => ['دلار', 'market_ticker_usd', 'USD_IRR_FREE'],
            'eur' => ['یورو', 'market_ticker_eur', 'EUR_IRR_FREE'],
            'gbp' => ['پوند انگلیس', 'market_ticker_gbp', 'GBP_IRR_FREE'],
            'usdt' => ['تتر', 'market_ticker_usdt', 'USDT_IRR'],
            'aed' => ['درهم امارات', 'market_ticker_aed', 'AED_IRR_FREE'],
            'try' => ['لیر ترکیه', 'market_ticker_try', 'TRY_IRR_FREE'],
            'gold_18' => ['طلای ۱۸ عیار', 'market_ticker_gold_18', 'GOLD_18K_IRR'],
            'gold_24' => ['طلای ۲۴ عیار', 'market_ticker_gold_24', 'GOLD_24K_IRR'],
            'gold_ounce' => ['انس طلا', 'market_ticker_gold_ounce', 'XAU_USD'],
            'melted_gold' => ['آبشده نقدی', 'market_ticker_melted_gold', 'GOLD_FUTURES'],
            'mesghal_gold' => ['مثقال طلا', 'market_ticker_mesghal_gold', 'GOLD_MESGHAL_IRR'],
            'new_coin' => ['سکه جدید', 'market_ticker_new_coin', 'COIN_EMAMI_IRR'],
            'old_coin' => ['سکه قدیم', 'market_ticker_old_coin', 'COIN_BAHAR_IRR'],
            'half_coin' => ['نیم سکه', 'market_ticker_half_coin', 'COIN_HALF_IRR'],
            'quarter_coin' => ['ربع سکه', 'market_ticker_quarter_coin', 'COIN_QUARTER_IRR'],
        ];
        $now = time();
        $items = [];

        foreach ($definitions as $key => $definition) {
            [$label, $setting, $symbol] = $definition;
            if (!$this->isEnabled($setting, true)) {
                continue;
            }
            $row = $bySymbol[$symbol] ?? null;
            if (!is_array($row) || !empty($row['stale']) || !empty($row['quality'])) {
                continue;
            }
            $raw = $row['price'] ?? null;
            if (!is_numeric($raw) || !is_finite((float) $raw) || (float) $raw <= 0) {
                continue;
            }
            // The individual quote timestamp, not the feed publication timestamp.
            $timestamp = isset($row['timestamp']) && is_string($row['timestamp'])
                ? strtotime($row['timestamp']) : false;
            if (!$timestamp || $timestamp > $now + 300 ||
                $timestamp < $now - self::MAX_QUOTE_AGE_SECONDS) {
                continue;
            }

            $factor = in_array($key, ['new_coin', 'old_coin', 'half_coin', 'quarter_coin'], true)
                ? 0.001 : 1.0;
            $change = $row['change'] ?? null;
            $items[] = [
                'key' => $key,
                'label' => $label,
                'value' => (float) $raw * $factor,
                'change' => is_numeric($change) && is_finite((float) $change)
                    ? (float) $change * $factor : null,
                'timestamp' => $timestamp,
            ];
        }
        return $items;
    }

    private function refreshSeconds(): int
    {
        // Ignore legacy 2-hour Navasan quota settings; this feed is keyless.
        $configured = (int) $this->setting('market_ticker_refresh_seconds', self::CACHE_SECONDS);
        return $configured >= 3600 ? self::CACHE_SECONDS
            : max(300, min(1800, $configured ?: self::CACHE_SECONDS));
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
            ? $this->settingsCache[$code] : $default;
    }
}
