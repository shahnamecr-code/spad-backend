<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminAdvertisementCategoryTreeResource extends JsonResource
{
    public function toArray($request)
    {
        $result=[
            'label'=>$this->name,
            'value'=>$this->id,
        ];
        $children=$this->children;
        if ($children && count($children)>0){
            $result['children']=self::collection($children);
        }
        return $result;
    }
}
