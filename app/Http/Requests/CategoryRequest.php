<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
            'name'=>'required',
            'slug'=>['required',Rule::unique('categories')->ignore($this->category)],
            'parent'=>'exists:categories,id|nullable',
            'order'=>'integer|nullable',
            'type'=>'nullable',
            'notification_limit'=>'integer|nullable',
            'description'=>'nullable',
            'icon'=>'nullable',
            'shortcut'=>'nullable',
            'banner'=>'nullable',
        ];
    }
}
