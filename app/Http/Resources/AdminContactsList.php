<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminContactsList extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name'=>$this->name,
            'mobile'=>$this->mobile,
            'subject'=>$this->subject,
            'content'=>$this->content,
            'created_at'=>$this->created_at->diffForHumans(),
            'status'=>$this->status,
            'status_text'=>$this->status_text,
        ];
    }
}
