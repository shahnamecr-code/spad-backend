<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;
    protected $guarded=[];
    public $timestamps=false;

    public function favoriteable()
    {
        return $this->morphTo();
    }

    public function advertisement(){
        return $this->morphedByMany(Advertisement::class,'favoriteable');
    }


}
