<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class MeetingTime extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $appends=['timestamp'];

    protected $dates=[
        'created_at','updated_at','time'
    ];

    protected $casts=[
        'status'=>'boolean'
    ];

    public function getTimestampAttribute()
    {
        return $this->time->getTimestamp();
    }

    public function getPersianTimeAttribute()
    {
        $time=$this->time;
        return (Jalalian::fromCarbon( $time ))->format('%A, %d %B %y');
    }
}
