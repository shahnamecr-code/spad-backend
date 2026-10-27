<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTourSession extends Model
{
    protected $fillable = [
        'tour_id', 'session_key', 'ip_hash', 'user_agent_hash', 'started_at', 'last_seen_at',
        'duration_seconds', 'scene_views', 'hotspot_clicks'
    ];
    protected $casts = ['started_at' => 'datetime', 'last_seen_at' => 'datetime'];
    public function tour() { return $this->belongsTo(VirtualTour::class, 'tour_id'); }
}
