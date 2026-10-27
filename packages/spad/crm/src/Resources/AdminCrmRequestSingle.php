<?php

namespace Spad\Crm\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCrmRequestSingle extends JsonResource
{
    public function toArray($request)
    {
        $properties=$this->properties->pluck(null,'property_slug')->toArray();
        $regions=[];
        if (isset($properties['position'])){
            $position=$properties['position'];
            if (isset($position['json_value']) && isset($position['json_value']['region'])){
                $regions=$position['json_value']['region'];
            }
        }
        $details=[];
        $crmFields=[];
        if ($this->category->crmFields){
            foreach ($this->category->crmFields as $crmField){
                $crmFields=array_merge($crmFields,$crmField['filters']);
            }
            $crmFields=array_column($crmFields,null,'property_id');
        }
        foreach ($properties as $index=>$property){
            if (isset($crmFields[$index])){
                if ($userShowCrmRequestSingle=userShowCrmRequestSingle($property,$crmFields[$index])){
                    $details[$index]=$userShowCrmRequestSingle;
                }
            }
        }

        return [
            'id'=>$this->id,
            'user'=>[
                'name'=>$this->user->full_name,
                'mobile'=>($this->admin_id==auth()->id())?$this->user->mobile:false
            ],
            'category'=>[
                'name'=>$this->category->name
            ],
            'city'=>[
                'name'=>$this->city->name
            ],
            'regions'=>array_column($regions,'name'),
            'date'=>$this->created_at->diffForHumans(),
            'properties'=>$details,
        ];
    }
}
