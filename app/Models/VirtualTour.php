<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTour extends Model
{
    protected $fillable = [
        'advertisement_id', 'title', 'slug', 'start_scene_id', 'floorplan_path',
        'status', 'created_by', 'settings', 'views_count', 'published_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'published_at' => 'datetime',
    ];

    protected $hidden = ['floorplan_path'];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function scenes()
    {
        return $this->hasMany(VirtualTourScene::class, 'tour_id')->orderBy('sort_order')->orderBy('id');
    }

    public function hotspots()
    {
        return $this->hasManyThrough(VirtualTourHotspot::class, VirtualTourScene::class, 'tour_id', 'scene_id');
    }

    public function startScene()
    {
        return $this->belongsTo(VirtualTourScene::class, 'start_scene_id');
    }

    public function floorplanPoints()
    {
        return $this->hasMany(VirtualTourFloorplanPoint::class, 'tour_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function sessions()
    {
        return $this->hasMany(VirtualTourSession::class, 'tour_id');
    }

}
