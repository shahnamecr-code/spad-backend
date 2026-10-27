<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBlogPostSingle extends JsonResource
{
    public function toArray($request)
    {
        $data=[
            'title'=>$this->title,
            'image'=>$this->mainImg?($this->mainImg->url):null,
            'slug'=>$this->slug,
            'short_description'=>$this->short_description,
            'description'=>$this->description,
            'meta_title'=>$this->meta_title,
            'meta_description'=>$this->meta_description,
            'categories'=>$this->categories->map->only(['name','slug']),
            'created_at'=>$this->created_at->diffForHumans(),
            'author'=>$this->admin->display_name,
            'show_sidebar'=>$this->show_sidebar,
            'sidebar_position'=>$this->sidebar_position,
        ];
        if ($this->show_sidebar){
            if ($this->sidebar){
                $data['sidebar']=$this->sidebar->widgets();
            }
        }
        return $data;
    }
}
