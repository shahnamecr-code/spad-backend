<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoCodeRequest extends FormRequest {
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
            'type' => ['required'],
            'auto' => ['required', 'boolean'],
            'code' => ['required_if:auto,false'],
            'amount' => ['required', 'integer'],
            'min_price' => ['nullable', 'integer'],
            'max_price' => ['nullable', 'integer'],
            'usage_count' => ['nullable', 'integer'],
            'cart_item_qty' => ['nullable', 'integer'],
            'start_date' => ['nullable'],
            'end_date' => ['nullable'],
            'category' => ['array'],
            'category_except' => ['array'],
            'product' => ['array'],
            'product_except' => ['array'],
            'customer' => ['array'],
            'customer_except' => ['array'],
            'customer_group' => ['array'],
            'customer_group_except' => ['array'],
        ];
    }

    /*protected function prepareForValidation() {
        $this->merge([
            'start_date' => Jalalian::forge($this->start_date)
                ->toCarbon()->format('Y/m/d H:i'),
            'end_date' => Jalalian::forge($this->end_date)->toCarbon()->format('Y/m/d H:m'),
        ]);
    }*/
}

