<?php

namespace App\Http\Resources;

use App\Models\Advertisement;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray($request)
    {
        switch ($this->favoriteable_type){
            case Advertisement::class:
                return new AdvertisementList($this->favoriteable);
            default:
                return parent::toArray($request);
        }

    }
}
