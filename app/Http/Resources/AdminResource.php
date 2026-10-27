<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'owner' => $this->owner,
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'roles' => $this->roles->pluck('name'),
            'address' => $this->address,
            'created_at' => $this->created_at
        ];
    }
}
