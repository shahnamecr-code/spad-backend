<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded=[];

    public function getStatusTextAttribute()
    {
        $status=$this->attributes['status'];
        switch ($status){
            case 0:
                return 'در انتظار بازرسی';
            case 1:
                return 'پاسخ داده شده';
        }
    }
}