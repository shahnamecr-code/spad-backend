<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTourFloorplanPoint extends Model
{
    protected $fillable = ['tour_id', 'scene_id', 'x', 'y', 'label'];
    protected $casts = ['x' => 'float', 'y' => 'float'];

    public function tour() { return $this->belongsTo(VirtualTour::class, 'tour_id'); }
    public function scene() { return $this->belongsTo(VirtualTourScene::class, 'scene_id'); }
}
