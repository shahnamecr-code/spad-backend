<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserExpertsList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "display_name" => $this->display_name,
            "email" => $this->email,
            "mobile" => $this->mobile,
            "profile"=>$this->avatar,
            "job_title"=>$this->job_title,
            "advertisements_count"=>$this->job_title,
            "meetings_count"=>$this->job_title,
            "crm_requests_count"=>$this->job_title,
            "point"=>$this->point,
            "internal_phone"=>$this->internal_phone,
        ];
    }
}
