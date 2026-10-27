<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id', 'order_id', 'user_id', 'sequence_no', 'code', 'status', 'purchased_at'
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'sequence_no' => 'integer',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function raffle() { return $this->belongsTo(PropertyRaffle::class, 'raffle_id'); }
    public function order() { return $this->belongsTo(RaffleOrder::class, 'order_id'); }
}
