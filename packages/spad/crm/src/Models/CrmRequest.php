<?php

namespace Spad\Crm\Models;

use App\Models\Admin;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmRequest extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded=[];

    protected $casts=[
        'regions'=>'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function properties()
    {
        return $this->hasMany(CrmRequestProperties::class)->with('property');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
