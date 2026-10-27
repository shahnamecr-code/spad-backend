<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id', 'user_id', 'admin_id', 'direction', 'type', 'amount',
        'balance_before', 'balance_after', 'status', 'reference', 'source_type',
        'source_id', 'title', 'description', 'meta'
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'meta' => 'array',
    ];

    public function wallet() { return $this->belongsTo(Wallet::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function admin() { return $this->belongsTo(Admin::class); }
}
