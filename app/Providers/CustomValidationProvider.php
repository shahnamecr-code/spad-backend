<?php

namespace App\Providers;

use App\Models\ProductTemplateAttribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class CustomValidationProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Validator::extend('unique_template_attribute', function($attribute, $value, $parameters)
        {
            $attribute_id=(int)$parameters[0];
            $product_template_id=isset($parameters[1])?(int)$parameters[1]:false;
            $query=ProductTemplateAttribute::where('attribute_id',$attribute_id);
            if($product_template_id){
                $query->where('product_template_id','!=',$product_template_id);
            }
            $cnt=$query->get()->where('value',$value)->count();
            return $cnt == 0;
        },'The :attribute must be unique.');
    }
}
