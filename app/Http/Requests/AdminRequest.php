<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return auth()->user()->can([
            'operators.create',
            'operators.edit',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'username' => ['required', Rule::unique('admins')->ignore($this->admin)],
            'email' => ['required', 'email'],
            'phone' => ['required'],
            'mobile' => ['required'],
            'password' => [
                'required',
                Password::min(8)
            ],
            'address' => ['required', 'string'],
            'shop_id'=> ['nullable',Rule::exists('shops','id')]
        ];
    }
}
