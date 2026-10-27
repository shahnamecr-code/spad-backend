<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest {
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
    public function rules() {
        return [
//            'email' => ['nullable', Rule::unique('users',)->ignore($this->id)],
//            'password' => [Rule::requiredIf(!$this->id)],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'mobile' => ['required', Rule::unique('users')->ignore($this->id)],
            'status' => ['required', 'boolean'],
        ];
    }
}
