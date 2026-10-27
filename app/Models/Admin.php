<?php

namespace App\Models;

use App\Models\Blog\BlogPost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spad\Crm\Models\CrmRequest;

class Admin extends Authenticatable {
    use HasFactory, HasApiTokens, HasRoles,SoftDeletes;

    public const APPROVED = 1;
    public const NOT_APPROVED = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'mobile',
        'status',
        'password',
        'address',
        'profile',
        'internal_phone',
        'job_title'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
    ];

    protected $appends=['display_name','avatar'];

    public function setPasswordAttribute($password) {
        $this->attributes['password'] = bcrypt($password);
    }

    public function markMobileAsVerified() {
        return $this->forceFill([
            'mobile_verified_at' => $this->freshTimestamp(),
        ])->save();
    }


    public function getPermissionArrayAttribute() {
        return $this->permissions->pluck('name');
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    public function regions()
    {
        return $this->belongsToMany(Region::class);
    }

    public function allowed_categories()
    {
        return $this->belongsToMany(Category::class,'admin_category','admin_id','category_id');
    }

    public function experts()
    {
        return $this->hasMany(self::class,'manager_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function advertisements()
    {
        return $this->morphMany(Advertisement::class, 'writable');
    }

    public function getIsSuperAdminAttribute()
    {
        return $this->hasRole('Super Admin');
    }

    public function getIsManagerAttribute()
    {
        return $this->hasRole('manager');
    }

    public function getIsSupervisorAttribute()
    {
        return $this->hasRole('supervisor');
    }

    public function getIsExpertAttribute()
    {
        return $this->hasRole('expert');
    }

    public function posts()
    {
        return $this->hasMany(BlogPost::class);
    }

    public function points()
    {
        return $this->morphMany(Point::class, 'pointable');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function crm_requests()
    {
        return $this->hasMany(CrmRequest::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function directories()
    {
        return $this->hasMany(Directory::class);
    }

    public function profile_file()
    {
        return $this->belongsTo(File::class,'profile');
    }

    public function getAvatarAttribute()
    {
        $profile_file=$this->profile_file;
        if ($profile_file){
            return $profile_file->url;
        }else{
            return user_default_profile();
        }
    }
}
