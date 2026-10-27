<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandTypeTransaction extends Model
{
    use HasFactory;
    protected $table='land_type_transaction';

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function land_type()
    {
        return $this->belongsTo(LandType::class);
    }

    public function getNameAttribute()
    {
        return "{$this->transaction->name} {$this->land_type->name}";
    }
}
