<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMeetingResource extends JsonResource
{
    public function toArray($request)
    {
        $status=$this->status;
        $status_text=$this->status_text;
        $active_time=$this->active_time->first();
        if ($active_time && $active_time->time->isPast()){
            $status=Meeting::EXPIRED;
            $status_text="منقضی شده";
        }

        return [
            'id'=>$this->id,
            'times'=>UserMeetingItemResource::collection($this->times),
            'advertisement'=>new AdminAdvertisementsList($this->advertisement),
            'status'=>$status,
            'status_text'=>$status_text,
            'user'=>$this->user
        ];
    }
}
