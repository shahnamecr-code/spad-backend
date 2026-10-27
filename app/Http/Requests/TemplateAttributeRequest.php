<?php

namespace App\Http\Requests;

use App\Models\Attribute;
use App\Rules\UniqueProp;
use Illuminate\Foundation\Http\FormRequest;

class TemplateAttributeRequest extends FormRequest
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
            'attributes'=>[
                function($attribute, $value, $fail) {
                    // index arr
                    $ids = array_keys($value);
                    // query to check if array keys is not valid
                    $stockCntWithinArrIDs = Attribute::whereIn('code', $ids)->count();
                    if ($stockCntWithinArrIDs != count($ids))
                        return $fail($attribute.' is invalid.');  // -> "quantity is invalid"
                }
            ],
//            'mainCat'=>['unique_template_attribute:1,77'],
        ];

        return $rules;
    }
}
