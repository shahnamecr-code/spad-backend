<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTourEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['tour_id', 'session_id', 'scene_id', 'hotspot_id', 'type', 'metadata', 'created_at'];
    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];
}
