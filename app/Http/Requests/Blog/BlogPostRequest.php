<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
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
            'title'=>['required'],
            'slug'=>['required',Rule::unique('blog_posts')->ignore($this->blogPost)],
            'categories.*'=>['exists:blog_categories,id'],
            'short_description'=>'nullable',
            'description'=>'nullable',
            'meta_title'=>'nullable',
            'meta_description'=>'nullable',
            'meta_keywords'=>'nullable',
            'status'=>'boolean',
            'start_time'=>['nullable'],
            'end_time'=>['nullable'],
            'image'=>['nullable','array'],
            'show_sidebar'=>['boolean'],
            'sidebar_position'=>['nullable','string',Rule::in(['right','left'])],
            'sidebar_id'=>['nullable',Rule::exists('sidebars','id')]
        ];
    }
}
