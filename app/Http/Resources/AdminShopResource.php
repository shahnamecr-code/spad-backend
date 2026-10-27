<?php

namespace App\Http\Resources;

use App\Models\Shop;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminShopResource extends JsonResource {
    //public static $wrap = 'data';
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request) {
        $result = [
            'id'=>$this->id,
            'name' => $this->name,
            'shaba_number' => $this->shaba_number,
            'shop_status' => $this->status == Shop::APPROVED,
            'state_id' => ''.$this->state_id,
            'city_id' => ''.$this->city_id,
            'post_code' => $this->post_code,
            'lat_and_long' => [$this->lat, $this->lng],
            'phone' => $this->admin->phone,
            'mobile' => $this->admin->mobile,
            'address' => $this->admin->address,
            'type' => $this->type,
            'user_first_name' => $this->admin->first_name,
            'user_last_name' => $this->admin->last_name,
            'email'=>$this->admin->email,
            'admin_status'=>$this->admin->status
        ];

        if ($this->type == 1) {
            $result = array_merge($result,[
                'first_name'=> $this->shopActual->first_name,
                'last_name'=> $this->shopActual->last_name,
                'birthday'=> $this->shopActual->birthday,
                'gender'=> $this->shopActual->gender,
                'identity_card_number'=> $this->shopActual->identity_card_number,
                'national_identity_number'=> $this->shopActual->national_identity_number,
            ]);
        } elseif ($this->type == 2) {
            $result = array_merge($result,[
                'company_name'=> $this->shopLegal->company_name,
                'company_type'=> ''.$this->shopLegal->company_type,
                'register_number'=> $this->shopLegal->register_number,
                'identity_number'=> $this->shopLegal->identity_number,
                'economic_number'=> $this->shopLegal->economic_number,
            ]);
        }
        return $result;
    }
}
