<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpadHomeDoctorFollowUp extends Model
{
    protected $table = 'spad_home_doctor_followups';
    protected $guarded = [];
    protected $casts = ['result' => 'array', 'consented_at' => 'datetime', 'analysis_started_at' => 'datetime'];
    public $incrementing = false;
    protected $keyType = 'string';
}
