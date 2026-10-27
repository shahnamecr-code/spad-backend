<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMeetingResource extends JsonResource
{
    public function toArray($request)
    {
        $status=$this->status;
        $status_text=$this->status_text;

        $active_time=$this->active_time->sortByDesc('time')->first();
        $latest_requested_time=$this->times->sortByDesc('time')->first();
        $reference_time=$active_time ?: $latest_requested_time;

        // A request is expired when its effective / latest requested time has passed,
        // unless it has already been canceled explicitly.
        if (
            $reference_time &&
            $reference_time->time->isPast() &&
            !in_array($this->status,[Meeting::USER_CANCELED,Meeting::ADMIN_CANCELED],true)
        ){
            $status=Meeting::EXPIRED;
            $status_text='منقضی شد';
        }

        return [
            'id'=>$this->id,
            'times'=>UserMeetingItemResource::collection($this->times->sortBy('time')->values()),
            'advertisement'=>new UserAdvertisementsList($this->advertisement),
            'status'=>$status,
            'status_text'=>$status_text,
            'created_at'=>$this->created_at ? $this->created_at->toISOString() : null,
            'can_edit'=>!in_array($status,[Meeting::EXPIRED,Meeting::USER_CANCELED,Meeting::ADMIN_CANCELED],true),
            'can_cancel'=>!in_array($status,[Meeting::EXPIRED,Meeting::USER_CANCELED,Meeting::ADMIN_CANCELED],true),
        ];
    }
}
