<?php

namespace App\Http\Resources;

use App\Models\Advertisement;
use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

class UserExpertInfoSingle extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data=parent::toArray($request);
        $data=[
            "id" => $this->id,
            "display_name" => $this->display_name,
            "email" => $this->email,
            "mobile" => $this->mobile,
            "profile"=>$this->avatar,
            "job_title"=>$this->job_title,
            "internal_phone"=>$this->internal_phone,
        ];
        $data["point"]=$this->points->average('point');
        $data["advertisements_count"]=$this->advertisements()->where('status',Advertisement::APPROVED)->count();
        $data["special_advertisements_count"]=$this->advertisements()->where('status',Advertisement::APPROVED)->whereHas('properties',function ($query){
            $query->where('property_slug','special')->where('boolean_value',true);
        })->count();
        $data["fast_advertisements_count"]=$this->advertisements()->where('status',Advertisement::APPROVED)->whereHas('properties',function ($query){
            $query->where('property_slug','fast')->where('boolean_value',true);
        })->count();
        $data["crm_requests_count"]=$this->crm_requests()->count();
        $data["meetings_count"]=$this->meetings()->count();

        $data['categories']=Category::query()->whereNull('parent')->select(['name','slug'])->get()->toArray();
        return $data;
    }
}
