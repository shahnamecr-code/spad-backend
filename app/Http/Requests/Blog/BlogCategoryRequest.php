<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
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
            'name'=>'required',
            'slug'=>['required',Rule::unique('blog_categories')->ignore($this->blogCategory)],
            'parent'=>'exists:blog_categories,id|nullable',
            'order'=>'integer|nullable',
            'description'=>'nullable',
            'status'=>'boolean',
            'image'=>'nullable',
        ];
    }
}
