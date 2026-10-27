<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'account_code' => ['required', 'string', Rule::unique('accounts')->where('shop_id', $this->request->get('shop_id'))->ignore($this->account)],
            'account_name' => ['required', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_bank' => ['nullable'],
            'shop_id' => [],
        ];
    }
}
