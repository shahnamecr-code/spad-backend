<?php

namespace App\Http\Requests;

use App\Models\Region;
use App\Support\SpadLocation;
use Illuminate\Foundation\Http\FormRequest;

class RegionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = trim((string) $this->input('name'));
            $name = strtr($name, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه']);
            $name = preg_replace('/\s+/u', ' ', $name);
            $this->merge(['name' => $name]);
        }
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'city_id' => ['nullable'],
            'area' => ['nullable'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('name') || $validator->errors()->has('name')) {
                return;
            }

            $current = $this->route('region');
            $currentId = is_object($current) ? (int) $current->id : (int) $current;
            $needle = $this->normalizeName($this->input('name'));

            $duplicate = Region::query()
                ->where('city_id', SpadLocation::cityId())
                ->when($currentId, function ($query) use ($currentId) {
                    $query->where('id', '!=', $currentId);
                })
                ->get(['id', 'name'])
                ->first(function ($region) use ($needle) {
                    return $this->normalizeName($region->name) === $needle;
                });

            if ($duplicate) {
                $validator->errors()->add('name', 'این خیابان قبلاً در لیست خیابان‌های شیراز ثبت شده است.');
            }
        });
    }

    private function normalizeName($value)
    {
        $value = trim((string) $value);
        $value = strtr($value, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه']);
        $value = str_replace(["\xE2\x80\x8C", "\xE2\x80\x8F"], '', $value);
        $value = preg_replace('/^(خیابان|بلوار|محله|محدوده|شهرک)\s+/u', '', $value);
        $value = preg_replace('/[\s\-_\/،,.]+/u', '', $value);

        return $value;
    }
}
