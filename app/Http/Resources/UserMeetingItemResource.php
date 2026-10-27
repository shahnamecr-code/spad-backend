<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class UserMeetingItemResource extends JsonResource
{
    public function toArray($request)
    {
        $time = $this->time;
        $jalali = Jalalian::fromCarbon($time);

        return [
            'id'=>$this->id,
            'status'=>$this->status,
            // Keep seconds for backward compatibility with the current API.
            'timestamp'=>$this->timestamp,
            'timestamp_ms'=>$this->timestamp * 1000,
            'date_text'=>$jalali->format('%A، %d %B %Y'),
            'clock_text'=>$time->format('H:i'),
            'time_text'=>$jalali->format('%A، %d %B %Y').' - ساعت '.$time->format('H:i'),
            'is_past'=>$time->isPast(),
        ];
    }
}
