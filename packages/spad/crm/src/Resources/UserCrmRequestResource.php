<?php

namespace Spad\Crm\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class UserCrmRequestResource extends JsonResource
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

        $admin=false;
        if ($this->admin){
            $admin=[
                'id'=>$this->id,
                'first_name'=>$this->admin->first_name,
                'last_name'=>$this->admin->last_name,
                'display_name'=>$this->admin->display_name,
                'mobile'=>$this->admin->mobile,
                'manager_id'=>$this->admin->manager_id,
            ];
        }

        $crm_layout=userShowCategoryCrmLayout($this->category);
        $groups=[];
        foreach ($crm_layout as $layout){
            if (isset($layout['items']) && count($layout['items'])>0){
                $items=collect(CrmRequestPropertiesResource::customCollection($this->properties,$layout['items']))->filter(function ($value, $key) {
                    return !is_null($value);
                })->toArray();
                if (count($items)>0){
                    $group=[
                        'label'=>$layout['label']??'',
                        'icon'=>isset($layout['icon'][0])?get_upload_url($layout['icon'][0]):'',
                        'items'=>$items
                    ];
                    $groups[]=$group;
                }
            }
        }

        return [
            'id'=>$this->id,
            'category'=>$this->category->name,
            'city'=>$this->city->name,
            'regions'=>array_column($regions,'name'),
            'date'=>$this->created_at->diffForHumans(),
            'admin'=>$admin,
            'properties'=>$groups
        ];
    }
}
