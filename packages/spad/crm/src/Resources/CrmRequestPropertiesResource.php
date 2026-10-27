<?php

namespace Spad\Crm\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CrmRequestPropertiesResource extends JsonResource
{
    private static $data;

    public function toArray($request)
    {
        if($this->property->type!="geo"){
            $categoryData=array_column(self::$data,null,'code');
            if (isset($categoryData[$this->property_slug])){
                $property=$categoryData[$this->property_slug];
//                return parent::toArray($request);
                $data=[
                    'label'=>$property['label']??'',
                    'range_search'=>$property['range_search']??false,
                    'search_type'=>$property['search_type']??false,
//                    'property'=>$this->property,
//                    'search_property'=>$property
                ];
                if (isset($property['range_search']) && $property['range_search']){
                    $data['value']=$this->json_value;
                }
                switch ($property['search_type']){
                    case 'text';
                        if ($data['range_search']){
                            $data['value']=$this->json_value;
                        }else{
                            $data['value']=$this->text_value;
                        }
                        break;
                    case 'select':
                    case 'radio':
                        if (isset($property['options'])){
                            if ($data['range_search']){
                                if (isset($data['value']['to'])){
                                    $to=collect($property['options'])->filter(function ($op)use($data){
                                        return $op['value']==$data['value']['to'];
                                    })->first();
                                    if ($to && isset($to['value'])){
                                        $data['value']['to']=$to['value'];
                                    } elseif($property['auto_complete']){
//                                        $v=$data['value']['to'];
                                    }else{
                                        unset($data['value']['to']);
                                    }
//                                    $data['value']['to']=collect($to)->first()['label'];
                                }
                                if (isset($data['value']['from'])){
                                    $from=collect($property['options'])->filter(function ($op)use($data){
                                        return $op['value']==$data['value']['from'];
                                    })->first();
                                    if ($from && isset($from['value'])){
                                        $data['value']['from']=$from['value'];
                                    } elseif($property['auto_complete']){
//                                        $v=$data['value']['from'];
                                    }else{
                                        unset($data['value']['from']);
                                    }
//                                    $data['value']['to']=collect($to)->first()['label'];
                                }
                            }else{
                                if ($op=collect($property['options'])->filter(function ($op)use($data){
                                    return $op['value']==$data['value'];
                                })){
                                    $data['value']=$op;
                                }else{
                                    $data['value']='';
                                }
                            }
                        }
                        break;
                    case 'checkbox':
                    case 'multi-select':
                            if (isset($property['range_search']) && $property['range_search']){

                            }else{
                                $selected=$this->json_value;
                                if (isset($property['options']) && $selected){
                                    $op=collect($property['options'])->filter(function ($op)use($selected){
                                        return in_array($op['value'],$selected);
                                    });
                                    $data['value']=$op->pluck('label');
                                }
                            }
                        break;
                    case 'switch':
                        if (isset($property['range_search']) && $property['range_search']){

                        }else{
                            $data['value']=$this->boolean_value;
                        }
                        break;
                }
                return $data;
            }
        }
    }

    public static function customCollection($resource, $data): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        //you can add as many params as you want.
        self::$data = $data;
        return parent::collection($resource);
    }
}
