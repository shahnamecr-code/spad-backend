<?php

namespace App\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory,SpatialTrait;
    public $timestamps=false;
    protected $guarded=[];

    protected $spatialFields = [
        'area'
    ];


    public function getProvinceAttribute() {
        return $this->city->province;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
