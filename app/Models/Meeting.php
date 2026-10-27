<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded=[];

    public const INIT=0;
    public const DONE=1;
    public const ADMIN_CANCELED=2;
    public const ADMIN_CHANGED=3;
    public const USER_CANCELED=4;
    public const USER_CHANGED=5;
    public const EXPIRED=6;

    public function times()
    {
       return $this->hasMany(MeetingTime::class);
    }

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }


    public function active_time()
    {
        return $this->hasMany(MeetingTime::class)->where('status',true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status){
            case self::INIT:
                return 'در انتظار بررسی';
            case self::DONE:
                return 'تایید شده';
            case self::ADMIN_CANCELED:
                return 'لغو توسط کارشناس';
            case self::USER_CANCELED:
                return 'لغو توسط کاربر';
            case self::ADMIN_CHANGED:
                return 'تغییر زمان بازدید توسط کارشناس';
            case self::USER_CHANGED:
                return 'تغییر زمان بازدید توسط کاربر';
            case self::EXPIRED:
                return 'منقضی شد';
        }
    }
}
