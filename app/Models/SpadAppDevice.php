<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpadAppDevice extends Model
{
    protected $table = 'spad_app_devices';
    protected $guarded = [];
    protected $hidden = ['push_token'];
    protected $casts = [
        'notifications_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}
