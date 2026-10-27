<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopRequest extends FormRequest
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
    public function rules(Request $request)
    {
        $emailUnique = Rule::unique('admins','email');
        if($this->shop){
            $emailUnique->ignore($this->shop->admin);
        }
        $rules = [
            'user_first_name' => ['required'],
            'user_last_name' => ['required'],
            'email' => ['required', 'email',$emailUnique],
            'password' => [Rule::requiredIf($request->isMethod('POST'))],
            'admin_status' => ['required','boolean'],
            'type' => ['required', 'in:1,2'],
        ];

        if($request->has('type') && $type = $request->get('type')){
            if ($type == 1) {
                $rules = array_merge($rules,[
                    'first_name' => ['required'],
                    'last_name' => ['required'],
                    'birthday' => ['required'],
                    'gender' => ['required', 'in:women,men'],
                    'identity_card_number' => ['required'],
                    'national_identity_number' => ['required', 'min:10'],
                ]);
            } elseif ($type == 2) {
                $rules = array_merge($rules,[
                    'company_name' => ['required', 'string'],
                    'company_type' => ['required', 'in:0,1,2,3,4,5'],
                    'register_number' => ['required', 'min:10'],
                    'identity_number' => ['required', 'min:10'],
                    'economic_number' => ['required', 'min:10']
                ]);
            }
        }else{
            return $rules;
        }
        $rules = array_merge($rules,[
            'city_id' => ['required'],
            'state_id' => ['required'],
            'post_code' => ['required', 'string', 'min:10'],
            'phone' => ['required'],
            'mobile' => ['required'],
            'lat_and_long' => ['required', 'array'],
            'address' => ['required', 'string', 'min:10'],
            'name' => ['required', Rule::unique('shops','name')->ignore($this->shop)],
            'shaba_number' => ['required', 'min:24', 'max:24'],
            'shop_status' => ['required','boolean'],
        ]);

        return $rules;
    }

    protected function prepareForValidation() {

    }
}
