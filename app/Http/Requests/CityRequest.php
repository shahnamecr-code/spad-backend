<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['nullable'],
            'lat' => ['required'],
            'lng' => ['required'],
            'area' => ['nullable'],
        ];
    }
}
