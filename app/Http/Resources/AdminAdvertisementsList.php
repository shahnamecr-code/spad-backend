<?php

namespace App\Http\Resources;

use App\Models\Advertisement;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAdvertisementsList extends JsonResource
{
    public function toArray($request)
    {
        $listSetting=$this->category->listSetting;

        $properties=$this->properties()->whereIn('property_slug',['title','gallery','position'])->with(['property'])->get();

        $props=[];
        foreach ($properties as $property){
            $props[$property->property_slug]=userShowAdvertisementProperty($property);
        }

        $main_properties=[];
        if(isset($listSetting['panel_custom_main_list']) && count($listSetting['panel_custom_main_list'])>0){
            $main_properties=$this->properties()->whereIn('property_slug',array_column($listSetting['panel_custom_main_list'],'slug'))->with(['property'])->get();
        }

        $main_props=[];
        foreach ($main_properties as $property){
            $main_props[]=userShowAdvertisementProperty($property);
        }

        $secondary_properties=[];
        if(isset($listSetting['panel_custom_secondary_list']) && count($listSetting['panel_custom_secondary_list'])>0){
            $secondary_properties=$this->properties()->whereIn('property_slug',array_column($listSetting['panel_custom_secondary_list'],'slug'))->with(['property'])->get();
        }

        $secondary_props=[];
        foreach ($secondary_properties as $property){
            $secondary_props[]=userShowAdvertisementProperty($property);
        }

        $images=$this->files()->wherePivot('collection_name','gallery')->get()->pluck('url')->toArray();
        if (count($images)>0){
            $img=$images[array_key_first($images)];
        }else{
            $img=url('/images/default/adds_default.jpg');
        }

        $data=[
            'id'=>$this->id,
            'category'=>$this->category->name,
            'title'=>isset($props['title'])?($props['title']['value']):'آگهی بدون عنوان',
            'image'=>$img,
            'city'=>($this->city)?($this->city->name):'',
            'region'=>($this->region)?($this->region->name):'',
            'date'=>$this->created_at->diffForHumans(),
            'main_props'=>$main_props,
            'secondary_props'=>$secondary_props,
            'status_text'=>$this->status_text,
            'status_class'=>$this->status_class,
        ];

        if ($this->status==Advertisement::REJECTED){
            $data['reject_reason']=$this->reject_reason;
        }

        if ($this->reports_count){
            $data['reports_count']=$this->reports_count;
        }

        return $data;
    }
}
