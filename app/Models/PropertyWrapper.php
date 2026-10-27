<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyWrapper extends Model
{
    use HasFactory;

    protected $fillable=[
        'title', 'order', 'position','layout'
    ];

    protected $casts=[
        'layout'=>'array'
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function scopeOrder($query)
    {
        return $query->orderBy('order','asc');
    }

    public function scopeSide($query)
    {
        return $query->where('position','side');
    }

    public function scopeNormal($query)
    {
        return $query->where('position','normal');
    }
}
