<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerAddressRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request) {
        $sell_info = (boolean)$request->get('self_info', false);
        return [
            'province' => ['integer','required'],
            'address' => ['required'],
            'city' => ['integer','required'],
            'pelak' => ['required'],
            'vahed' => ['nullable'],
            'lat'=>['nullable'],
            'lng'=>['nullable'],
            'post_code' => ['required'],
            'self_info' => ['required', 'boolean'],
            'first_name' => [Rule::requiredIf(!$sell_info)],
            'last_name' => [Rule::requiredIf(!$sell_info)],
            'phone' => [Rule::requiredIf(!$sell_info)],
            'mobile' => [Rule::requiredIf(!$sell_info)],
        ];
    }
}
