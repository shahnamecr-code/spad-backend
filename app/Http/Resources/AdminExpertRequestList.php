<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminExpertRequestList extends JsonResource
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
            'guest'=>$this->guest,
            'category'=>$this->category->name,
            'first_name'=>($this->guest)?($this->first_name):($this->user->first_name),
            'last_name'=>($this->guest)?($this->last_name):($this->user->last_name),
            'mobile'=>($this->guest)?($this->mobile):($this->user->mobile),
            'province'=>($this->province)?($this->province->name):'',
            'city'=>($this->city)?($this->city->name):'',
            'region'=>($this->region)?($this->region->name):'',
            'address'=>$this->address,
            'date'=>$this->created_at->diffForHumans()
        ];
    }
}
