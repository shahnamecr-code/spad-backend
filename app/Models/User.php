<?php

namespace App\Models;

use App\Models\Accounts\Factor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spad\Crm\Models\CrmRequest;

class User extends Authenticatable {
    use HasFactory, Notifiable, HasApiTokens,SoftDeletes;

    const APPROVED = 1;
    const REJECTED = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'mobile',
        'status',
        'phone_verified_at'
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
    ];

    protected $appends = ['full_name'];


    public function getFullNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function setPasswordAttribute($password) {
        $this->attributes['password'] = bcrypt($password);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class)->with('favoriteable');
    }

    public function advertisements()
    {
        return $this->morphMany(Advertisement::class, 'writable');
    }

    public function expert_requests()
    {
        return $this->hasMany(ExpertRequest::class);
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

    public function partnerPanels()
    {
        return $this->hasMany(PartnerPanel::class);
    }

    public function activePartnerPanels()
    {
        return $this->partnerPanels()->active();
    }
}
