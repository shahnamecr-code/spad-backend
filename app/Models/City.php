<?php

namespace App\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory,SpatialTrait;
    public $timestamps=false;
    protected $guarded=[];

    protected $spatialFields = [
        'area'
    ];

    protected $casts=[
        'is_default'=>'boolean'
    ];

    public function regions()
    {
        return $this->hasMany(Region::class,'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

}
