<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Setting;
use Illuminate\Http\Request;

class PropertyToolsController extends Controller
{
    public function show(Request $request, Advertisement $advertisement)
    {
        if ($advertisement->status !== Advertisement::APPROVED || $advertisement->is_investor_exclusive) {
            abort(404);
        }

        return response()->json(['data' => $this->payload($advertisement)]);
    }

    public function payload(Advertisement $advertisement): array
    {
        $tool = $advertisement->toolConfig()->with('virtualTourFile')->first();

        return [
                'virtual_tour_enabled' => (bool) optional($tool)->virtual_tour_enabled,
                'virtual_tour_url' => optional($tool)->virtual_tour_url,
                'virtual_tour_file_url' => optional(optional($tool)->virtualTourFile)->url,
                'virtual_tour_file_type' => optional(optional($tool)->virtualTourFile)->type,
                'renovation_enabled' => (bool) optional($tool)->renovation_enabled,
                'renovation_url' => optional($tool)->renovation_url,
                'rental_deposit_ceiling' => optional($tool)->rental_deposit_ceiling,
                'rental_monthly_rent' => optional($tool)->rental_monthly_rent,
                'rental_occupancy_status' => optional($tool)->rental_occupancy_status,
                'rental_evacuation_date' => optional($tool)->rental_evacuation_date ? optional($tool)->rental_evacuation_date->format('Y-m-d') : null,
                'mortgage_enabled' => (bool) optional($tool)->mortgage_enabled,
                'mortgage_max_amount' => optional($tool)->mortgage_max_amount,
                'document_type' => optional($tool)->document_type,
                'owner_identity_verified' => (bool) optional($tool)->owner_identity_verified,
                'documents_verified' => (bool) optional($tool)->documents_verified,
                'below_expert_price' => (bool) optional($tool)->below_expert_price,
                'floorplan_3d_enabled' => (bool) optional($tool)->floorplan_3d_enabled,
                'floorplan_3d_url' => optional($tool)->floorplan_3d_url,
            'mortgage_settings' => $this->mortgageSettings(),
        ];
    }

    private function mortgageSettings(): array
    {
        $codes = [
            'mortgage_enabled',
            'mortgage_default_interest_rate',
            'mortgage_terms_years',
            'mortgage_min_amount',
            'mortgage_step_amount',
            'mortgage_bond_loan_per_sheet',
            'mortgage_bond_price_per_sheet',
            'mortgage_note',
        ];

        $settings = Setting::query()
            ->where('group', 'mortgage_setting')
            ->whereIn('code', $codes)
            ->get()
            ->pluck('value', 'code');

        $termsRaw = $this->normalizeDigits((string)($settings['mortgage_terms_years'] ?? ''));
        $terms = collect(preg_split('/[,،]/u', $termsRaw))
            ->map(fn ($value) => (int) trim($value))
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'enabled' => filter_var($settings['mortgage_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'annual_interest_rate' => $this->decimalSetting($settings['mortgage_default_interest_rate'] ?? 0),
            'terms_years' => $terms,
            'min_amount' => $this->integerSetting($settings['mortgage_min_amount'] ?? 0),
            'step_amount' => max(1, $this->integerSetting($settings['mortgage_step_amount'] ?? 1000000)),
            'bond_loan_per_sheet' => $this->integerSetting($settings['mortgage_bond_loan_per_sheet'] ?? 0),
            'bond_price_per_sheet' => $this->integerSetting($settings['mortgage_bond_price_per_sheet'] ?? 0),
            'note' => (string)($settings['mortgage_note'] ?? ''),
        ];
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private function integerSetting($value): int
    {
        $value = $this->normalizeDigits((string)$value);
        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits !== '' ? (int)$digits : 0;
    }

    private function decimalSetting($value): float
    {
        $value = $this->normalizeDigits((string)$value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return is_numeric($value) ? (float)$value : 0.0;
    }
}
