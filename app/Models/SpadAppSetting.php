<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpadAppSetting extends Model
{
    protected $table = 'spad_app_settings';
    protected $fillable = ['key', 'value'];
}
