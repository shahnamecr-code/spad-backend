<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployRequest extends Model
{
    use HasFactory;

    protected $fillable=[
        "name","mobile","second_mobile",
        "postal_code","province_id","city_id", "address",
        "study_field","study_degree",
        "job_demand", "work_experience",
        "guarantee","description"
    ];
    protected $casts=[
        "work_experience"=>"array",
        "guarantee"=>"boolean"
    ];

    const STUDY_DEGREES=[
        'دیپلم','فوق دیپلم','لیسانس','فوق لیسانس'
    ];

    const JOB_DEMANDS=[
        'مدیر فروش','کارشناس فروش - حرفه ای','مشاور - مبتدی','منشی (فقط خانم)',
        'سرایدار','حسابدار','برنامه نویس','طراح و بلاگر'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
