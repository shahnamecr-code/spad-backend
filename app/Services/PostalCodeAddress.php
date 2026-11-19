<?php

namespace App\Services;

/** Normalize structured address data before issuing a postal valuation receipt. */
class PostalCodeAddress
{
    private const FIELDS = [
        'province', 'city', 'town', 'district', 'street', 'street2',
        'number', 'floor', 'sideFloor', 'buildingName', 'address',
    ];

    public static function normalize(array $payload): array
    {
        $partial = [];
        foreach (self::records($payload) as $record) {
            $details = self::normalizeRecord($record);
            if (isset($details['province'], $details['city'])) {
                return $details;
            }
            if (!$partial && $details) {
                $partial = $details;
            }
        }
        return $partial;
    }

    private static function normalizeRecord(array $record): array
    {
        $details = [];
        foreach (self::FIELDS as $field) {
            $key = strtolower($field);
            $placeName = $field === 'province' || $field === 'city';
            $value = self::text($record[$key] ?? null, $placeName);
            if ($placeName && $value === null) {
                $value = self::text($record[$key . 'name'] ?? null, true);
            }
            if ($value !== null) {
                $details[$field] = mb_substr($value, 0, 240);
            }
        }

        $latitude = $record['lat'] ?? $record['latitude'] ?? null;
        $longitude = $record['long'] ?? $record['longitude'] ?? null;
        if (is_numeric($latitude) && is_numeric($longitude) &&
            $latitude >= 24 && $latitude <= 40 && $longitude >= 43 && $longitude <= 64) {
            $details['lat'] = (float) $latitude;
            $details['long'] = (float) $longitude;
        }
        return $details;
    }

    /**
     * Accept the documented flat object and bounded single-address wrappers.
     * Multiple address records cannot be treated as one verified address.
     */
    private static function records(array $payload, int $depth = 0): array
    {
        if ($depth > 3 || $payload === []) {
            return [];
        }
        if (array_keys($payload) === range(0, count($payload) - 1)) {
            return count($payload) === 1 && is_array($payload[0])
                ? self::records($payload[0], $depth + 1)
                : [];
        }

        $normalized = [];
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $field = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
                if (!array_key_exists($field, $normalized) || $key === $field) {
                    $normalized[$field] = $value;
                }
            }
        }

        $records = [$normalized];
        foreach (['address', 'data', 'result'] as $container) {
            if (isset($normalized[$container]) && is_array($normalized[$container])) {
                $records = array_merge($records, self::records($normalized[$container], $depth + 1));
            }
        }
        return $records;
    }

    private static function text($value, bool $placeName): ?string
    {
        if ($placeName && is_array($value)) {
            $value = $value['nameFa'] ?? $value['name'] ?? null;
        }
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }

        $value = trim(preg_replace('/^[\s\x{200c}\x{200e}\x{200f}]+|[\s\x{200c}\x{200e}\x{200f}]+$/u', '', (string) $value) ?? '');
        if ($value === '') {
            return null;
        }
        if ($placeName) {
            $normalized = strtolower(preg_replace('/[\s\x{200c}\x{200e}\x{200f}]+/u', '', $value) ?? '');
            if (!preg_match('/\p{L}/u', $value) ||
                in_array($normalized, ['null', 'none', 'n/a', 'unknown', 'نامشخص', 'نامعلوم'], true)) {
                return null;
            }
        }
        return $value;
    }
}
