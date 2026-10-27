<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminAdvertisementReport extends JsonResource
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
            'id'=>$this->id,
            'reason'=>$this->reason,
            'description'=>$this->description,
            'user'=>$this->user,
            'created_at'=>$this->created_at->diffForHumans()
        ];
    }
}
