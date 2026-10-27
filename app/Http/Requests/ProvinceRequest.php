<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvinceRequest extends FormRequest {


    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'name' => ['required', Rule::unique('provinces')->ignore($this->province)],
            'lat' => ['required'],
            'lng' => ['required'],
            'area' => ['nullable'],
        ];
    }
}
