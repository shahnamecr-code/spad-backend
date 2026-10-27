<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    const WAITING=1;
    const APPROVED=2;
    const REJECTED=3;

    protected $fillable=[
        'category_id','status',
        'city_id','region_id','address',
        'writable_id','writable_type',
        'priority',
        'important_places'
    ];
    public $timestamps=['created_at','updated_at','priority'];

    protected $casts=[
        'important_places'=>'array'
    ];


    public function investorListing()
    {
        return $this->hasOne(InvestorListing::class);
    }

    public function scopePublicCatalog($query)
    {
        // Any live InvestorListing record keeps the advertisement out of the public
        // catalogue, regardless of whether that private deal is pending, active,
        // rejected or archived. Releasing it publicly must be an explicit admin action.
        return $query->whereDoesntHave('investorListing');
    }

    public function getIsInvestorExclusiveAttribute()
    {
        return $this->investorListing()->exists();
    }

    public function properties()
    {
        return $this->hasMany(AdvertisementProperty::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function getStatusTextAttribute()
    {
        switch ($this->attributes['status']){
            case self::APPROVED:
                return 'تایید شده';
            case self::REJECTED:
                return 'رد شده';
            default:
                return 'در انتظار بررسی';
        }
    }

    public function getStatusClassAttribute()
    {
        switch ($this->attributes['status']){
            case self::APPROVED:
                return 'success';
            case self::REJECTED:
                return 'danger';
            default:
                return 'secondary';
        }
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class,'advertisement_admin','advertisement_id','admin_id');
    }

    public function writable()
    {
        return $this->morphTo();
    }

    public function reports()
    {
        return $this->morphMany(Reporting::class, 'reportable');
    }

    public function scopeNew($query)
    {
        return $query->orderBy('priority','desc');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoriteable');
    }

    public function points()
    {
        return $this->morphMany(Point::class, 'pointable');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    public function getDistanceToPlacesAttribute()
    {
        $important_places=$this->important_places;
        if ($important_places){
            array_walk($important_places,function (&$val){
                if (isset($val['place']['icon'])){
                    $val['place']['icon']=url($val['place']['icon']);
                }
            });
        }
        return $important_places??[];
    }

    public function toolConfig()
    {
        return $this->hasOne(AdvertisementTool::class);
    }

    public function virtualTours()
    {
        return $this->hasMany(VirtualTour::class);
    }

    public function publishedVirtualTour()
    {
        return $this->hasOne(VirtualTour::class)->where('status', 'published')->latest('published_at');
    }

}
