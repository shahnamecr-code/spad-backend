<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTourHotspot extends Model
{
    protected $fillable = [
        'scene_id', 'target_scene_id', 'type', 'title', 'content', 'yaw', 'pitch', 'icon',
        'media_path', 'link_url', 'settings', 'sort_order', 'clicks_count'
    ];

    protected $casts = [
        'settings' => 'array',
        'yaw' => 'float',
        'pitch' => 'float',
    ];

    protected $hidden = ['media_path'];

    public function scene()
    {
        return $this->belongsTo(VirtualTourScene::class, 'scene_id');
    }

    public function targetScene()
    {
        return $this->belongsTo(VirtualTourScene::class, 'target_scene_id');
    }

}
