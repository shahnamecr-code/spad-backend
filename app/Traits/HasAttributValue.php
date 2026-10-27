<?php
namespace App\Traits;

use App\Models\Attribute;

trait HasAttributValue{

    public function property()
    {
        return $this->belongsTo(Attribute::class,'attribute_id')->with('options');
    }

    public function getValueAttribute(){
        if($this->property){
            $type=$this->property->type;
            return $this->{get_attribute_type($type)};
        }
    }

    public function setValAttribute($val){
        $type=$this->property->type;
        switch ($type){
            case 'image':
                $this->text_value=$val;
                break;
            case 'gallery':
                $this->json_value=$val;
                break;
            case 'select':
                $this->text_value=$val;
                break;
            case 'boolean':
                $this->boolean_value=$val;
                break;
            case 'color':
                break;
            case 'textarea':
                $this->longtext_value=$val;
                break;
            default:
                $this->text_value=$val;
        }
    }
}
