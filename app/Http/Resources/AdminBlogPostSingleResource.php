<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminBlogPostSingleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'admin_id'=>$this->admin_id,
            'description'=>$this->description,
            'id'=>$this->id,
            'image'=>$this->mainImg?([$this->mainImg->only(['id','url'])]):null,
            'meta_description'=>$this->meta_description,
            'meta_keywords'=>$this->meta_keywords,
            'meta_title'=>$this->meta_title,
            'short_description'=>$this->short_description,
            'show_sidebar'=>$this->show_sidebar,
            'sidebar_id'=>$this->sidebar_id,
            'sidebar_position'=>$this->sidebar_position,
            'start_time'=>$this->start_time?(1000*$this->start_time->getTimestamp()):null,
            'end_time'=>$this->end_time?(1000*$this->end_time->getTimestamp()):null,
            'status'=>$this->status,
            'title'=>$this->title,
            'slug'=>$this->slug,
        ];
    }
}
