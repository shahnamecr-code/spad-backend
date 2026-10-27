<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AdvertisementProperty extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $casts=[
        'json_value'=>'array',
        'integer_value'=>'integer',
        'boolean_value'=>'boolean'
    ];
//    protected $appends=[
//        'value'
//    ];

    public function property()
    {
        return $this->belongsTo(Property::class,'property_slug','slug');
    }

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function getValueAttribute()
    {
        $property=$this->property;
        switch ($property->type){
            case 'text':
            case 'select':
            case 'radio':
                return $this->text_value;
            case 'textarea':
                return $this->textarea_value;
            case 'boolean':
                return $this->boolean_value;
            case 'integer':
                return $this->integer_value;
            case 'multiselect':
            case 'checkbox':
                return $this->json_value;
            case 'images':
                return File::query()->whereIn('id',$this->json_value)->get()->map(function ($file){
                    return collect($file)->only(['id','url'])->toArray();
                });
        }
    }

    public function getPanelValueAttribute()
    {
        $property=$this->property;
        switch ($property->type){
            case 'text':
            case 'select':
            case 'radio':
                return $this->text_value;
            case 'textarea':
                return $this->textarea_value;
            case 'boolean':
                return $this->boolean_value;
            case 'integer':
                return $this->integer_value;
            case 'multiselect':
            case 'checkbox':
                return $this->json_value;
            case 'images':
                return $this->advertisement->files()->wherePivot('collection_name',$property->slug)->get()->map(function ($file){
                    return collect($file)->only(['id','url'])->toArray();
                });
        }
    }

    public function setValueAttribute($value)
    {
        $property=$this->property;
        switch ($property->type){
            case 'text':
            case 'select':
            case 'radio':
                $this->attributes['text_value']=$value;
                break;
            case 'textarea':
                $this->attributes['textarea_value']=$value;
                break;
            case 'boolean':
                $this->attributes['boolean_value']=$value;
                break;
            case 'integer':
                $this->attributes['integer_value']=$value;
                break;
            case 'multiselect':
            case 'checkbox':
                $this->attributes['json_value']=$value;
                break;
            case 'images':
                break;
        }
    }

    public function setImagesAttribute($ad)
    {
        $property=$this->property;
        switch ($property->type){
            case 'images':
                if (is_array($this->json_value)){
                    $ad->files()->wherePivot('collection_name',$property->slug)->syncWithPivotValues($this->json_value,['collection_name'=>$property->slug]);
                }
                break;
        }
    }
}
