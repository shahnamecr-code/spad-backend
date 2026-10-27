<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'raffle_id', 'gateway_id', 'ticket_count', 'amount',
        'gateway_amount', 'gateway_currency', 'status', 'tracking_code',
        'callback_token', 'transaction_id', 'reference_id', 'expires_at',
        'paid_at', 'failure_message', 'meta'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
        'ticket_count' => 'integer',
        'amount' => 'integer',
        'gateway_amount' => 'integer',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function raffle() { return $this->belongsTo(PropertyRaffle::class, 'raffle_id'); }
    public function gateway() { return $this->belongsTo(RaffleGateway::class, 'gateway_id'); }
    public function tickets() { return $this->hasMany(RaffleTicket::class, 'order_id'); }
}
