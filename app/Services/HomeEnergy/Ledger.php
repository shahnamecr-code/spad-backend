<?php

namespace App\Services\HomeEnergy;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;

class Ledger
{
    public const UTILITIES = [
        'water' => ['label' => 'آب', 'unit' => 'متر مکعب', 'unit_field' => 'water_bill_id'],
        'electricity' => ['label' => 'برق', 'unit' => 'کیلووات‌ساعت', 'unit_field' => 'electricity_bill_id'],
        'gas' => ['label' => 'گاز', 'unit' => 'متر مکعب', 'unit_field' => 'gas_bill_id'],
        'phone' => ['label' => 'تلفن ثابت', 'unit' => null, 'unit_field' => 'landline_bill_id'],
    ];

    public static function digits($value)
    {
        if (!is_string($value)) {
            return null;
        }
        $normalized = preg_replace('/[\s\x{200c}-]+/u', '', strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
        return $normalized === '' ? null : $normalized;
    }

    public static function identifierHash(string $identifier, int $owner): string
    {
        return hash_hmac('sha256', $owner . ':' . $identifier, (string) config('app.key'));
    }

    public static function account($record): array
    {
        $utility = self::UTILITIES[$record->utility];
        return [
            'id' => (int) $record->id, 'location_id' => (int) $record->location_id, 'title' => $record->title,
            'utility' => $record->utility, 'identifier' => Crypt::decryptString($record->identifier_encrypted),
            'source' => $record->source_unit_id ? 'building_unit' : 'manual',
            'label' => $utility['label'], 'unit' => $utility['unit'],
        ];
    }

    public static function bill($record): array
    {
        return [
            'id' => (int) $record->id, 'account_id' => (int) $record->account_id,
            'amount_toman' => (int) $record->amount_toman, 'consumption' => $record->consumption === null ? null : (float) $record->consumption,
            'period_start' => $record->period_start, 'period_end' => $record->period_end,
            'issued_on' => $record->issued_on, 'due_on' => $record->due_on, 'remind_on' => $record->remind_on,
            'payment_identifier' => $record->payment_identifier_encrypted ? Crypt::decryptString($record->payment_identifier_encrypted) : null,
            'paid_on' => $record->paid_on, 'payment_status' => $record->paid_on ? 'owner_recorded' : 'unpaid',
            'has_receipt' => (bool) $record->receipt_path,
        ];
    }

    public static function compare(array $series): ?array
    {
        if (count($series) < 2) {
            return null;
        }
        $latest = $series[0];
        $latestDays = self::days($latest);
        foreach (array_slice($series, 1) as $candidate) {
            $previousDays = self::days($candidate);
            if ($candidate['period_end'] >= $latest['period_start'] || abs($latestDays - $previousDays) > ceil($latestDays * .2)) {
                continue;
            }
            return [
                'current' => $latest, 'previous' => $candidate,
                'current_days' => $latestDays, 'previous_days' => $previousDays,
                'cost_change_percent' => self::percent($latest['amount_toman'], $candidate['amount_toman']),
                'consumption_daily_change_percent' => $latest['consumption'] !== null && $candidate['consumption'] !== null
                    ? self::percent($latest['consumption'] / $latestDays, $candidate['consumption'] / $previousDays) : null,
            ];
        }
        return null;
    }

    private static function days(array $bill): int
    {
        return CarbonImmutable::parse($bill['period_start'])->diffInDays(CarbonImmutable::parse($bill['period_end'])) + 1;
    }

    private static function percent(float $current, float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }
}
