<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserExpertRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'province'=>($this->province)?$this->province->name:'',
            'city'=>($this->city)?$this->city->name:'',
            'region'=>($this->region)?$this->region->name:'',
            'transaction'=>($this->category)?$this->category->name:'',
            'applicationType'=>($this->category && $this->category->parent_category)?$this->category->parent_category->name:'',
            'landType'=>($this->category && $this->category->parent_category && $this->category->parent_category->parent_category)?$this->category->parent_category->parent_category->name:'',
            'status'=>$this->status,
            'address'=>$this->address,
            'status_text'=>$this->status_text,
        ];
    }
}
