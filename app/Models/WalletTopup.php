<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'wallet_id', 'gateway_id', 'amount', 'gateway_amount',
        'gateway_currency', 'status', 'tracking_code', 'callback_token',
        'transaction_id', 'reference_id', 'expires_at', 'paid_at',
        'failure_message', 'meta'
    ];

    protected $casts = [
        'amount' => 'integer',
        'gateway_amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function wallet() { return $this->belongsTo(Wallet::class); }
    public function gateway() { return $this->belongsTo(WalletGateway::class, 'gateway_id'); }
}
