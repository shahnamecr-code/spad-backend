<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SpadPropertyPortfolio
{
    public const MAX_ASSETS = 30;
    public const REFRESH_HOURS = 6;

    public function save(array $quote, int $userId, string $nickname): array
    {
        $postal = (string) ($quote['postal_code'] ?? '');
        $hash = hash_hmac('sha256', $postal, (string) config('app.key'));
        $snapshot = $quote['valuation'];
        $details = $quote['address'] ?? [];
        $name = trim($nickname);
        if ($name === '') $name = 'ملک من در ' . ($quote['city'] ?? 'ایران');

        return DB::transaction(function () use ($quote, $userId, $name, $postal, $hash, $snapshot, $details) {
            $query = DB::table('spad_user_property_assets')
                ->where('user_id', $userId)->where('postal_hash', $hash);
            $existing = $query->lockForUpdate()->first();
            if (!$existing && DB::table('spad_user_property_assets')->where('user_id', $userId)->count() >= self::MAX_ASSETS) {
                throw new \RuntimeException('حداکثر ۳۰ ملک قابل ذخیره است.', 422);
            }

            $values = [
                'nickname' => mb_substr($name, 0, 100),
                'postal_cipher' => Crypt::encryptString($postal),
                'address_cipher' => Crypt::encryptString(json_encode($details, JSON_UNESCAPED_UNICODE)),
                'province' => (string) $quote['province'],
                'city' => (string) $quote['city'],
                'place_id' => (int) $snapshot['place_id'],
                'place_name' => (string) $snapshot['place_name'],
                'match_level' => (string) $snapshot['match_level'],
                'property_kind' => (string) $snapshot['property_kind'],
                'area_m2' => (float) $snapshot['area_m2'],
                'rooms' => $quote['filters']['rooms'] ?? null,
                'age' => $quote['filters']['age'] ?? null,
                'parking' => $quote['filters']['parking'] ?? null,
                'elevator' => $quote['filters']['elevator'] ?? null,
                'price_per_m2_toman' => (int) $snapshot['price_per_m2_toman'],
                'estimated_total_toman' => (int) $snapshot['estimated_total_toman'],
                'range_low_toman' => $snapshot['range_low_toman'],
                'range_high_toman' => $snapshot['range_high_toman'],
                'data_points' => $snapshot['data_points'] ?? null,
                'coverage' => (string) ($snapshot['coverage'] ?? 'unknown'),
                'source_computed_at' => $this->safeDateText($snapshot['computed_at'] ?? null),
                'source_url' => $this->safeSourceUrl($snapshot['source_url'] ?? null),
                'refresh_error' => null,
                'last_checked_at' => now(),
                'last_success_at' => now(),
                'updated_at' => now(),
            ];
            if ($existing) {
                $query->update($values);
                $id = (int) $existing->id;
            } else {
                $values['user_id'] = $userId;
                $values['postal_hash'] = $hash;
                $values['created_at'] = now();
                $id = (int) DB::table('spad_user_property_assets')->insertGetId($values);
            }
            $this->snapshot($id, (int) $snapshot['price_per_m2_toman'],
                (int) $snapshot['estimated_total_toman'], $this->safeDateText($snapshot['computed_at'] ?? null));
            return $this->present(DB::table('spad_user_property_assets')->where('id', $id)->first());
        });
    }

    private function safeDateText($value): ?string
    {
        if (!is_string($value) || strlen($value) > 64 || !preg_match('/^[0-9]{4}-[0-9T:.+Z\-]{5,60}$/', $value)) return null;
        return $value;
    }

    private function safeSourceUrl($url): string
    {
        return is_string($url) && preg_match('#^https://(www\.)?nemuda\.com(/[a-z0-9/-]*)?$#i', $url)
            ? mb_substr($url, 0, 255) : 'https://nemuda.com/';
    }

    private function snapshot(int $id, int $pricePerM2, int $total, ?string $sourceAt): void
    {
        $last = DB::table('spad_user_property_prices')
            ->where('asset_id', $id)->orderByDesc('id')->first();
        // A point is real only when the source publishes new data or a value changes.
        if ($last && (int) $last->estimated_total_toman === $total
            && (string) ($last->source_computed_at ?? '') === (string) ($sourceAt ?? '')) return;
        DB::table('spad_user_property_prices')->insert([
            'asset_id' => $id,
            'price_per_m2_toman' => $pricePerM2,
            'estimated_total_toman' => $total,
            'source_computed_at' => $sourceAt,
            'captured_at' => now(),
        ]);
    }

    private function areaBucket(string $kind, float $area): string
    {
        $limits = [
            'apartment' => [[60, 'lt60'], [80, '60-80'], [100, '80-100'], [150, '100-150'], [INF, 'gte150']],
            'house' => [[100, 'lt100'], [150, '100-150'], [250, '150-250'], [INF, 'gte250']],
            'land' => [[250, 'lt250'], [500, '250-500'], [1000, '500-1000'], [10000, '1000-10000'], [INF, 'gte10000']],
            'shop' => [[20, 'lt20'], [40, '20-40'], [80, '40-80'], [INF, 'gte80']],
            'office' => [[60, 'lt60'], [100, '60-100'], [200, '100-200'], [INF, 'gte200']],
        ];
        foreach ($limits[$kind] ?? [] as [$limit, $key]) {
            if ($area < $limit) return $key;
        }
        return $kind === 'apartment' ? 'gte150' : 'gte200';
    }

    public function refresh($asset, NemudaPropertyMarket $market, bool $force = false): bool
    {
        $last = $asset->last_checked_at ? \Carbon\Carbon::parse($asset->last_checked_at) : null;
        if (!$force && $last && $last->gt(now()->subHours(self::REFRESH_HOURS))) return false;

        $lock = Cache::lock('spad:asset-refresh:' . (int) $asset->id, 40);
        if (!$lock->get()) return false;
        try {
            // A refresh must NEVER use a paid postcode lookup again.
            $facets = [
                'dealType' => 'sell',
                'propertyKind' => (string) $asset->property_kind,
                'area' => $this->areaBucket((string) $asset->property_kind, (float) $asset->area_m2),
            ];
            if (in_array($asset->property_kind, ['apartment', 'office', 'house'], true) && $asset->age) $facets['age'] = $asset->age;
            if ($asset->property_kind === 'apartment' && $asset->rooms) $facets['rooms'] = $asset->rooms;
            if (in_array($asset->property_kind, ['apartment', 'office'], true) && $asset->parking) $facets['parking'] = $asset->parking;
            if ($asset->property_kind === 'apartment' && $asset->elevator) $facets['elevator'] = $asset->elevator;

            $base = ['last_checked_at' => now(), 'updated_at' => now()];
            try {
                $prices = $market->priceDetails($market->place((int) $asset->place_id, $facets));
                if (!$prices || $prices['coverage'] === 'none' || (float) $prices['price_per_m2'] <= 0) {
                    DB::table('spad_user_property_assets')->where('id', $asset->id)
                        ->update($base + ['refresh_error' => 'insufficient_market_data']);
                    return false;
                }
                $rate = (int) round($prices['price_per_m2']);
                $total = (int) round($prices['price_per_m2'] * (float) $asset->area_m2);
                $date = $this->safeDateText($prices['computed_at'] ?? null);
                DB::transaction(function () use ($asset, $prices, $rate, $total, $date, $base) {
                    DB::table('spad_user_property_assets')->where('id', $asset->id)->update($base + [
                        'price_per_m2_toman' => $rate,
                        'estimated_total_toman' => $total,
                        'range_low_toman' => $prices['range_low'] ? (int) round($prices['range_low'] * (float) $asset->area_m2) : null,
                        'range_high_toman' => $prices['range_high'] ? (int) round($prices['range_high'] * (float) $asset->area_m2) : null,
                        'data_points' => $prices['data_points'],
                        'coverage' => $prices['coverage'],
                        'source_computed_at' => $date,
                        'refresh_error' => null,
                        'last_success_at' => now(),
                    ]);
                    $this->snapshot((int) $asset->id, $rate, $total, $date);
                });
                return true;
            } catch (\Throwable $e) {
                DB::table('spad_user_property_assets')->where('id', $asset->id)
                    ->update($base + ['refresh_error' => 'market_temporarily_unavailable']);
                return false;
            }
        } finally {
            $lock->release();
        }
    }

    public function present($asset): array
    {
        $address = [];
        try {
            $address = json_decode(Crypt::decryptString($asset->address_cipher), true) ?: [];
        } catch (\Throwable $e) {
            // Never disclose corrupted ciphertext.
        }
        $history = DB::table('spad_user_property_prices')->where('asset_id', $asset->id)
            ->orderByDesc('id')->limit(60)->get()->reverse()->values();
        $first = $history->first();
        $total = (int) $asset->estimated_total_toman;
        $firstTotal = $first ? (int) $first->estimated_total_toman : null;
        $delta = $firstTotal !== null && $total > 0 ? $total - $firstTotal : null;
        $changePct = $firstTotal && $firstTotal > 0 && $delta !== null
            ? round($delta * 100 / $firstTotal, 2) : null;
        return [
            'id' => (int) $asset->id,
            'nickname' => $asset->nickname,
            'province' => $asset->province,
            'city' => $asset->city,
            'address' => is_array($address) ? ($address['address'] ?? implode('، ', array_filter([
                $asset->province, $asset->city, $address['street'] ?? '', $address['number'] ?? ''
            ]))) : '',
            'place_name' => $asset->place_name,
            'match_level' => $asset->match_level,
            'property_kind' => $asset->property_kind,
            'area_m2' => (float) $asset->area_m2,
            'price_per_m2_toman' => $asset->price_per_m2_toman !== null ? (int) $asset->price_per_m2_toman : null,
            'estimated_total_toman' => $asset->estimated_total_toman !== null ? $total : null,
            'range_low_toman' => $asset->range_low_toman !== null ? (int) $asset->range_low_toman : null,
            'range_high_toman' => $asset->range_high_toman !== null ? (int) $asset->range_high_toman : null,
            'coverage' => $asset->coverage,
            'data_points' => $asset->data_points !== null ? (int) $asset->data_points : null,
            'source_computed_at' => $asset->source_computed_at,
            'source_url' => $this->safeSourceUrl($asset->source_url),
            'last_checked_at' => $asset->last_checked_at,
            'last_success_at' => $asset->last_success_at,
            'refresh_error' => $asset->refresh_error,
            'stale' => !$asset->last_success_at ||
                \Carbon\Carbon::parse($asset->last_success_at)->lt(now()->subHours(24)),
            'delta_since_saved_toman' => count($history) > 1 ? $delta : null,
            'delta_since_saved_percent' => count($history) > 1 ? $changePct : null,
            'history' => $history->map(function ($r) {
                return [
                    'estimated_total_toman' => (int) $r->estimated_total_toman,
                    'price_per_m2_toman' => (int) $r->price_per_m2_toman,
                    'source_computed_at' => $r->source_computed_at,
                    'captured_at' => $r->captured_at,
                ];
            })->all(),
            'created_at' => $asset->created_at,
            'ownership_verified' => false,
        ];
    }
}
