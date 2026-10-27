<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertRequest extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id','category_id','first_name','last_name', 'mobile','guest',
        'province_id','city_id','region_id','address'
    ];

    protected $casts=[
        'guest'=>'boolean'
    ];

    public const INIT = 0;
    public const APPROVED=1;
    public const REJECTED=2;

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getStatusTextAttribute()
    {
        switch ($this->attributes['status']){
            case self::INIT:
                return 'در انتظار بازرسی';
            case self::APPROVED:
                return 'تایید شده';
            case self::REJECTED:
                return 'رد شده';
        }
    }

}
