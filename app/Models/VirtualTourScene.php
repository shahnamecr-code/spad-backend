<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTourScene extends Model
{
    protected $fillable = [
        'tour_id', 'uuid', 'title', 'original_path', 'ultra_path', 'high_path', 'medium_path',
        'preview_path', 'thumbnail_path', 'width', 'height', 'initial_yaw', 'initial_pitch',
        'initial_fov', 'sort_order', 'process_status', 'process_error', 'metadata', 'views_count'
    ];

    protected $casts = [
        'metadata' => 'array',
        'initial_yaw' => 'float',
        'initial_pitch' => 'float',
        'initial_fov' => 'float',
    ];

    protected $hidden = [
        'original_path', 'ultra_path', 'high_path', 'medium_path', 'preview_path', 'thumbnail_path'
    ];

    public function tour()
    {
        return $this->belongsTo(VirtualTour::class, 'tour_id');
    }

    public function hotspots()
    {
        return $this->hasMany(VirtualTourHotspot::class, 'scene_id')->orderBy('sort_order')->orderBy('id');
    }

    public function incomingHotspots()
    {
        return $this->hasMany(VirtualTourHotspot::class, 'target_scene_id');
    }

    public function floorplanPoint()
    {
        return $this->hasOne(VirtualTourFloorplanPoint::class, 'scene_id');
    }

}
