<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttributeRequest extends FormRequest
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
        $rules=[
            //
            'code'=>['required',Rule::unique('attributes')->ignore($this->attribute)],
            'name'=>'required',
            'type'=>'required',
            'options'=>'array',
            'is_variable'=>'',
        ];

        return $rules;
    }
}
