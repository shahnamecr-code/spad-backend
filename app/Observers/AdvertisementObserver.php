<?php

namespace App\Observers;

use App\Models\Advertisement;
use App\Models\AdvertisementTool;
use App\Models\Admin;

class AdvertisementObserver
{
    public function creating(Advertisement $advertisement)
    {
        $advertisement->priority = now();
    }

    public function saved(Advertisement $advertisement)
    {
        // The saved event runs for create/update even when the advertisement's own columns
        // did not become dirty. This guarantees property_tools are persisted when an admin
        // edits only the virtual-tour / renovation / finance tools.
        $this->syncPropertyTools($advertisement);
    }

    public function deleted(Advertisement $advertisement)
    {
        AdvertisementTool::where('advertisement_id', $advertisement->id)->delete();
    }

    public function restored(Advertisement $advertisement)
    {
        // no-op
    }

    public function forceDeleted(Advertisement $advertisement)
    {
        AdvertisementTool::where('advertisement_id', $advertisement->id)->delete();
    }

    private function syncPropertyTools(Advertisement $advertisement): void
    {
        if (!request()->has('property_tools')) {
            return;
        }

        $tools = request()->input('property_tools', []);
        if (!is_array($tools)) {
            return;
        }

        // Only update keys that were actually submitted. This is important for
        // customer-side editing where users can change document_type without
        // overwriting admin-only mortgage / virtual-tour configuration.
        $updates = [];

        if (array_key_exists('virtual_tour_files', $tools) || array_key_exists('virtual_tour_file_id', $tools)) {
            $fileId = data_get($tools, 'virtual_tour_files.0.id');
            if (!$fileId) {
                $fileId = data_get($tools, 'virtual_tour_file_id');
            }
            $updates['virtual_tour_file_id'] = $this->nullableInteger($fileId);
        }
        if (array_key_exists('virtual_tour_url', $tools)) {
            $updates['virtual_tour_url'] = $this->nullableString(data_get($tools, 'virtual_tour_url'));
        }
        if (array_key_exists('virtual_tour_enabled', $tools)) {
            $updates['virtual_tour_enabled'] = $this->toBoolean(data_get($tools, 'virtual_tour_enabled'));
        }
        if (array_key_exists('renovation_enabled', $tools)) {
            $updates['renovation_enabled'] = $this->toBoolean(data_get($tools, 'renovation_enabled'));
        }
        if (array_key_exists('renovation_url', $tools)) {
            $updates['renovation_url'] = $this->nullableString(data_get($tools, 'renovation_url'));
        }
        if (array_key_exists('rental_deposit_ceiling', $tools)) {
            $updates['rental_deposit_ceiling'] = $this->moneyInteger(data_get($tools, 'rental_deposit_ceiling'));
        }
        if (array_key_exists('rental_monthly_rent', $tools)) {
            $updates['rental_monthly_rent'] = $this->moneyInteger(data_get($tools, 'rental_monthly_rent'));
        }
        if (array_key_exists('rental_occupancy_status', $tools)) {
            $status = $this->rentalOccupancyStatus(data_get($tools, 'rental_occupancy_status'));
            $updates['rental_occupancy_status'] = $status;
            $updates['rental_evacuation_date'] = $status === 'tenant'
                ? $this->nullableDate(data_get($tools, 'rental_evacuation_date'))
                : null;
        } elseif (array_key_exists('rental_evacuation_date', $tools)) {
            $currentStatus = optional($advertisement->toolConfig()->first())->rental_occupancy_status ?: 'vacant';
            $updates['rental_evacuation_date'] = $currentStatus === 'tenant'
                ? $this->nullableDate(data_get($tools, 'rental_evacuation_date'))
                : null;
        }
        if (array_key_exists('mortgage_enabled', $tools)) {
            $updates['mortgage_enabled'] = $this->toBoolean(data_get($tools, 'mortgage_enabled'));
        }
        if (array_key_exists('mortgage_max_amount', $tools)) {
            $updates['mortgage_max_amount'] = $this->moneyInteger(data_get($tools, 'mortgage_max_amount'));
        }
        if (array_key_exists('document_type', $tools)) {
            $updates['document_type'] = $this->documentType(data_get($tools, 'document_type'));
        }

        // Management badges are admin-only. Even if a customer tampers with
        // the request payload, these values are ignored unless the authenticated
        // actor is an Admin model.
        if (request()->user() instanceof Admin) {
            if (array_key_exists('owner_identity_verified', $tools)) {
                $updates['owner_identity_verified'] = $this->toBoolean(data_get($tools, 'owner_identity_verified'));
            }
            if (array_key_exists('documents_verified', $tools)) {
                $updates['documents_verified'] = $this->toBoolean(data_get($tools, 'documents_verified'));
            }
            if (array_key_exists('below_expert_price', $tools)) {
                $updates['below_expert_price'] = $this->toBoolean(data_get($tools, 'below_expert_price'));
            }
            if (array_key_exists('floorplan_3d_enabled', $tools)) {
                $updates['floorplan_3d_enabled'] = $this->toBoolean(data_get($tools, 'floorplan_3d_enabled'));
            }
            if (array_key_exists('floorplan_3d_url', $tools)) {
                $updates['floorplan_3d_url'] = $this->nullableString(data_get($tools, 'floorplan_3d_url'));
            }
        }

        if (!$updates) {
            return;
        }

        AdvertisementTool::updateOrCreate(
            ['advertisement_id' => $advertisement->id],
            $updates
        );
    }

    private function toBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function nullableInteger($value): ?int
    {
        return is_numeric($value) && (int)$value > 0 ? (int)$value : null;
    }

    private function nullableString($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value !== '' ? mb_substr($value, 0, 1000) : null;
    }

    private function documentType($value): ?string
    {
        $allowed = ['single_leaf', 'endowment', 'pre_sale'];
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private function rentalOccupancyStatus($value): string
    {
        return is_string($value) && $value === 'tenant' ? 'tenant' : 'vacant';
    }

    private function nullableDate($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function moneyInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = strtr((string)$value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits !== '' ? (int)$digits : null;
    }
}
