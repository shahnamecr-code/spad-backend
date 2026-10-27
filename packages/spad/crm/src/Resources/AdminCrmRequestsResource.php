<?php
namespace Spad\Crm\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCrmRequestsResource extends JsonResource
{

    public function toArray($request)
    {
        $properties=$this->properties->pluck(null,'property_slug')->toArray();
        $regions=[];
        if (isset($properties['position'])){
            $position=$properties['position'];
            if (isset($position['json_value']) && isset($position['json_value']['region'])){
                $regions=$position['json_value']['region'];
            }
        }

        $can_edit=false;
        $can_transfer=false;
        $allowed_categories=auth()->user()->allowed_categories;

        if (auth()->user()->is_super_admin){
            $can_edit=true;
            $can_transfer=true;
        } elseif (auth()->user()->is_manager){
            $allowed_cities=auth()->user()->cities;
            if ($allowed_categories && $allowed_categories->contains($this->category) && $allowed_cities && $allowed_cities->contains($this->city)){
                $can_edit=true;
                $can_transfer=true;
            }
        }elseif(auth()->user()->is_supervisor){
            $experts=auth()->user()->experts;
            if ($experts && $experts->contains($this->admin)){
                $can_edit=true;
            }
        }

        if ($this->admin_id==auth()->id()){
            $can_edit=true;
        }

        $admin=false;
        if ($this->admin){
            $admin=[
                'id'=>$this->id,
                'first_name'=>$this->admin->first_name,
                'last_name'=>$this->admin->last_name,
                'display_name'=>$this->admin->display_name,
                'mobile'=>$this->admin->mobile,
                'manager_id'=>$this->admin->manager_id,
            ];
        }

        return [
            'id'=>$this->id,
            'user'=>[
                'name'=>$this->user->full_name
            ],
            'category'=>[
                'name'=>$this->category->name
            ],
            'city'=>[
                'name'=>$this->city->name
            ],
            'regions'=>array_column($regions,'name'),
            'date'=>$this->created_at->diffForHumans(),
            'can_edit'=>$can_edit,
            'can_transfer'=>$can_transfer,
            'admin'=>$admin
        ];
    }
}
