<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandType extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $guarded=[];

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class);
    }

}
