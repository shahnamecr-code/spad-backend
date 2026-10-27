<?php

namespace App\Http\Resources;

use App\Models\Advertisement;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAdvertisementsList extends JsonResource
{
    public function toArray($request)
    {
        $listSetting=$this->category->listSetting;

        $properties=$this->properties()->whereIn('property_slug',['title','gallery','position','fast','special','luxury','netting'])->with(['property'])->get();

        $props=[];
        foreach ($properties as $property){
            $props[$property->property_slug]=userShowAdvertisementProperty($property);
        }

        // Use raw booleans for status badges. Formatted boolean values are
        // "دارد/ندارد" strings and must not be used as truthy flags.
        $booleanBadges=$properties
            ->whereIn('property_slug',['fast','special','luxury','netting'])
            ->pluck('boolean_value','property_slug');

        $main_properties=[];
        if(isset($listSetting['front_custom_main_list']) && count($listSetting['front_custom_main_list'])>0){
            $main_properties=$this->properties()->whereIn('property_slug',array_column($listSetting['front_custom_main_list'],'slug'))->with(['property'])->get();
        }

        $main_props=[];
        foreach ($main_properties as $property){
            $main_props[]=userShowAdvertisementProperty($property);
        }

        $secondary_properties=[];
        if(isset($listSetting['front_custom_secondary_list']) && count($listSetting['front_custom_secondary_list'])>0){
            $secondary_properties=$this->properties()->whereIn('property_slug',array_column($listSetting['front_custom_secondary_list'],'slug'))->with(['property'])->get();
        }

        $secondary_props=[];
        foreach ($secondary_properties as $property){
            $secondary_props[]=userShowAdvertisementProperty($property);
        }

        // These facts may not be part of a category's configurable list layout.
        // Read them from the advertisement itself for the mobile home cards.
        $summaryProperties=$this->properties()
            ->whereHas('property', function ($query) {
                $query->where('title', 'like', '%متراژ%')
                    ->orWhere('title', 'like', '%مساحت%')
                    ->orWhere('title', 'like', '%زیربنا%')
                    ->orWhere('title', 'like', '%عرصه%')
                    ->orWhere('title', 'like', '%سال ساخت%')
                    ->orWhere('title', 'like', '%سال بنا%')
                    ->orWhere('title', 'like', '%تعداد خواب%')
                    ->orWhere('title', 'like', '%اتاق خواب%')
                    ->orWhere('title', 'like', '%تعداد اتاق%');
            })->with('property')->get();
        $summary=['area'=>null, 'build_year'=>null, 'bedrooms'=>null];
        foreach ($summaryProperties as $property) {
            $title=$property->property->title ?? '';
            $formatted=userShowAdvertisementProperty($property);
            $value=$formatted['value'] ?? null;
            if ($value === null || $value === '') continue;
            if (!$summary['area'] && preg_match('/متراژ|مساحت|زیربنا|عرصه/u', $title)) {
                $summary['area']=$value;
            } elseif (!$summary['build_year'] && preg_match('/سال ساخت|سال بنا/u', $title)) {
                $summary['build_year']=$value;
            } elseif (!$summary['bedrooms'] && preg_match('/تعداد خواب|اتاق خواب|تعداد اتاق/u', $title)) {
                $summary['bedrooms']=$value;
            }
        }

        $images=$this->files()->wherePivot('collection_name','gallery')->get()->pluck('url')->toArray();
        if (count($images)==0){
            $images[]=url('/images/default/adds_default.jpg');
        }

        $data=[
            'id'=>$this->id,
            'category'=>$this->category->name,
            'title'=>isset($props['title'])?($props['title']['value']):'آگهی بدون عنوان',
            'image'=>$images[0],
            'gallery'=>$images,
            'city'=>($this->city)?($this->city->name):'',
            'region'=>($this->region)?($this->region->name):'',
            'date'=>$this->created_at->diffForHumans(),
            'main_props'=>$main_props,
            'secondary_props'=>$secondary_props,
            'area'=>$summary['area'],
            'build_year'=>$summary['build_year'],
            'bedrooms'=>$summary['bedrooms'],
            'status_text'=>$this->status_text,
            'status_class'=>$this->status_class,
            'fast'=>(bool)($booleanBadges['fast']??false),
            'special'=>(bool)($booleanBadges['special']??false),
            'lux'=>(bool)($booleanBadges['luxury']??false),
            'netting'=>(bool)($booleanBadges['netting']??false),
        ];

        if ($this->status==Advertisement::REJECTED){
            $data['reject_reason']=$this->reject_reason;
        }

        return $data;
    }
}
