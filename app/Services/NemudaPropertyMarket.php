<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Public CC BY 4.0 market aggregates from Nemuda. No private address, postal
 * code or user identifier is ever forwarded to the market provider.
 */
class NemudaPropertyMarket
{
    private const BASE = 'https://api.nemuda.com/api/market/real-estate/';
    private const TTL = 21600;
    private $placeIndexCache = null;

    private function fetch(string $path, array $filters = []): array
    {
        $cacheKey = 'spad:nemuda:v1:' . sha1($path . '?' . http_build_query($filters));
        return Cache::remember($cacheKey, self::TTL, function () use ($path, $filters) {
            try {
                $response = Http::acceptJson()->timeout(15)->get(self::BASE . $path, $filters);
            } catch (\Throwable $e) {
                throw new RuntimeException('اتصال به داده‌های بازار نمودا برقرار نشد.', 503);
            }
            if (!$response->successful() || !is_array($response->json())) {
                throw new RuntimeException('سرویس بازار نمودا پاسخ معتبر نداد.', 503);
            }
            return $response->json();
        });
    }

    public function summary(): array
    {
        $response = $this->fetch('summary');
        return [
            'regions' => $this->items($response, ['provinces', 'places', 'items', 'regions']),
            'updated_at' => $this->date($response),
            'source' => 'نمودا',
        ];
    }

    public function children(int $placeId, array $filters = []): array
    {
        $response = $this->fetch('places/' . $placeId . '/children', $filters);
        return [
            'places' => $this->items($response, ['children', 'places', 'items', 'regions']),
            'updated_at' => $this->date($response),
            'source' => 'نمودا',
        ];
    }

    public function place(int $id, array $filters = []): array
    {
        return $this->fetch('places/' . $id, $filters);
    }

    public function index(): array
    {
        if (is_array($this->placeIndexCache)) return $this->placeIndexCache;
        $response = $this->fetch('place-index');
        $data = $this->unwrap($response);
        $sources = [];
        foreach (['places', 'items', 'locations', 'index', 'provinces', 'cities', 'neighborhoods'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) $sources[] = $data[$key];
        }
        if (!$sources && array_is_list($data)) $sources[] = $data;
        $rows = [];
        foreach ($sources as $source) {
            foreach ($source as $value) {
                if (!is_array($value)) continue;
                $id = $value['id'] ?? $value['placeId'] ?? null;
                $name = trim((string) ($value['nameFa'] ?? $value['name'] ?? $value['title'] ?? ''));
                if (!is_numeric($id) || (int) $id <= 0 || !$name) continue;
                $rows[] = [
                    'id' => (int) $id,
                    'name' => $name,
                    'type' => (string) ($value['placeKind'] ?? $value['type'] ?? $value['kind'] ?? $value['level'] ?? ''),
                    'parent_id' => isset($value['parentId']) ? (int) $value['parentId'] :
                        (isset($value['parent_id']) ? (int) $value['parent_id'] : null),
                    'path' => (string) ($value['parentNameFa'] ?? $value['path'] ?? $value['fullName'] ?? $value['full_name'] ?? $value['breadcrumb'] ?? $value['url'] ?? ''),
                    'slug' => (string) ($value['slug'] ?? ''),
                ];
            }
        }
        $this->placeIndexCache = $rows;
        return $rows;
    }

    /**
     * Find a single provably compatible city from the nationwide place index.
     * Never pick the first same-named city across multiple provinces.
     */
    public function matchCity(string $province, string $city): ?array
    {
        $rows = $this->index();
        $cityName = $this->normalize($city);
        $provinceName = $this->normalize($province);
        $candidates = array_values(array_filter($rows, function ($row) use ($cityName) {
            $type = strtolower($row['type']);
            return $this->normalize($row['name']) === $cityName &&
                !in_array($type, ['neighborhood', 'district', 'محله', 'province', 'استان'], true);
        }));
        if (count($candidates) === 1) return $candidates[0];
        if (!$candidates) return null;
        // Match province in path, or through its actual parent in the index.
        $byId = [];
        foreach ($rows as $row) $byId[$row['id']] = $row;
        $matches = array_values(array_filter($candidates, function ($row) use ($provinceName, $byId) {
            if ($provinceName && str_contains($this->normalize($row['path']), $provinceName)) return true;
            $parent = $row['parent_id'] ? ($byId[$row['parent_id']] ?? null) : null;
            return $parent && $this->normalize($parent['name']) === $provinceName;
        }));
        return count($matches) === 1 ? $matches[0] : null;
    }

    public function priceDetails(array $payload): ?array
    {
        $node = $this->unwrap($payload);
        $candidates = [$node];
        foreach (['place', 'market', 'stats', 'summary', 'metrics', 'result', 'price', 'value', 'data'] as $key) {
            if (isset($node[$key]) && is_array($node[$key])) {
                $candidates[] = $node[$key];
                foreach (['market', 'stats', 'summary', 'metrics', 'price'] as $nested) {
                    if (isset($node[$key][$nested]) && is_array($node[$key][$nested]))
                        $candidates[] = $node[$key][$nested];
                }
            }
        }
        foreach ($candidates as $market) {
            $raw = $market['pricePerM2'] ?? $market['price_per_m2'] ?? null;
            if (!is_numeric($raw) || (float) $raw <= 0) continue;
            $range = $market['pricePerM2Range'] ?? $market['price_per_m2_range'] ?? [];
            if (!is_array($range)) $range = [];
            $low = $range['low'] ?? null;
            $high = $range['high'] ?? null;
            $count = $market['dataPoints'] ?? $market['sampleCount'] ?? null;
            $coverage = (string) ($market['coverage'] ?? '');
            return [
                'price_per_m2' => (float) $raw,
                'range_low' => is_numeric($low) && $low > 0 ? (float) $low : null,
                'range_high' => is_numeric($high) && $high > 0 ? (float) $high : null,
                'data_points' => is_numeric($count) ? (int) $count : null,
                'coverage' => in_array($coverage, ['solid', 'limited', 'none'], true) ? $coverage : 'unknown',
                'computed_at' => $market['computedAt'] ?? $node['computedAt'] ?? $payload['computedAt'] ?? null,
                'window_days' => $market['windowDays'] ?? $node['windowDays'] ?? null,
                'slug' => (string) ($node['slug'] ?? ''),
            ];
        }
        return null;
    }

    private function unwrap(array $data): array
    {
        foreach (['data', 'result'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) return $data[$key];
        }
        return $data;
    }

    private function date(array $response): ?string
    {
        $data = $this->unwrap($response);
        return $data['computedAt'] ?? $response['computedAt'] ?? $data['updatedAt'] ?? null;
    }

    private function items(array $response, array $keys): array
    {
        $data = $this->unwrap($response);
        $rows = [];
        if (array_is_list($data)) $rows = $data;
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rows = array_values($data[$key]);
                break;
            }
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $id = $row['id'] ?? $row['placeId'] ?? null;
            $name = trim((string) ($row['nameFa'] ?? $row['name'] ?? $row['title'] ?? ''));
            if (!is_numeric($id) || (int) $id < 1 || !$name) continue;
            $detail = $this->priceDetails($row);
            $out[] = [
                'id' => (int) $id,
                'name' => $name,
                'price_per_m2' => $detail['price_per_m2'] ?? null,
                'data_points' => $detail['data_points'] ?? null,
                'coverage' => $detail['coverage'] ?? 'unknown',
                'computed_at' => $detail['computed_at'] ?? $this->date($response),
                'slug' => (string) ($row['slug'] ?? ''),
                'source_url' => $this->urlForPlace((int) $id),
            ];
        }
        return $out;
    }

    /** Canonical attribution for CC BY 4.0: link to that exact Nemuda place. */
    public function urlForPlace(int $placeId): string
    {
        $byId = [];
        foreach ($this->index() as $row) $byId[$row['id']] = $row;
        $segments = [];
        $seen = [];
        $current = $placeId;
        for ($depth = 0; $depth < 6; $depth++) {
            if (!isset($byId[$current]) || isset($seen[$current])) break;
            $seen[$current] = true;
            $row = $byId[$current];
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '' || !preg_match('/^[a-z0-9-]+$/i', $slug)) break;
            array_unshift($segments, strtolower($slug));
            if (!$row['parent_id']) break;
            $current = (int) $row['parent_id'];
        }
        return $segments ? 'https://nemuda.com/' . implode('/', $segments) : 'https://nemuda.com/';
    }

    public function normalize(string $text): string
    {
        $text = strtr($text, ['ي' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه', '‌' => ' ', 'أ' => 'ا', 'إ' => 'ا']);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{06D6}-\x{06ED}]/u', '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        return preg_replace('/^(استان|شهر)\s+/u', '', $text);
    }
}
