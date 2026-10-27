<?php

namespace App\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory,SpatialTrait;
    public $timestamps=false;
    protected $guarded=[];

    protected $spatialFields = [
        'area'
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
