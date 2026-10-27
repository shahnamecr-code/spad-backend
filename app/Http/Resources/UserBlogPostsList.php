<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBlogPostsList extends JsonResource
{
    public function toArray($request)
    {
        $data=[
            'title'=>$this->title,
            'image'=>$this->mainImg?($this->mainImg->url):null,
            'slug'=>$this->slug,
            'short_description'=>$this->short_description,
            'meta_title'=>$this->meta_title,
            'meta_description'=>$this->meta_description,
            'categories'=>$this->categories->map->only(['name','slug']),
            'created_at'=>$this->created_at->diffForHumans(),
            'author'=>$this->admin->display_name,
        ];

        return $data;
    }
}
