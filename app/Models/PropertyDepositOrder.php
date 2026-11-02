<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyDepositOrder extends Model
{
    protected $fillable = [
        'advertisement_id', 'user_id', 'gateway_id', 'amount', 'gateway_amount',
        'gateway_currency', 'status', 'tracking_code', 'callback_token',
        'transaction_id', 'reference_id', 'expires_at', 'paid_at', 'failure_message',
        'property_code', 'property_title', 'property_location', 'gateway_title',
    ];

    protected $hidden = ['callback_token', 'transaction_id', 'failure_message'];

    protected $casts = [
        'amount' => 'integer',
        'gateway_amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function advertisement() { return $this->belongsTo(Advertisement::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function gateway() { return $this->belongsTo(WalletGateway::class, 'gateway_id'); }
}
