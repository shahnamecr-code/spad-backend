<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpadHomeDoctorCase extends Model
{
    protected $table = 'spad_home_doctor_cases';
    protected $guarded = [];
    protected $casts = [
        'answers' => 'array',
        'result' => 'array',
        'consented_at' => 'datetime',
        'analysis_started_at' => 'datetime',
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    public function media()
    {
        return $this->hasMany(SpadHomeDoctorMedia::class, 'case_id');
    }

    public function followUps()
    {
        return $this->hasMany(SpadHomeDoctorFollowUp::class, 'case_id');
    }
}
