<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_wallet_id', 'partner_panel_id', 'inquiry_request_id', 'direction', 'type', 'amount',
        'balance_before', 'balance_after', 'status', 'reference', 'title', 'description', 'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'meta' => 'array',
    ];

    public function wallet() { return $this->belongsTo(PartnerWallet::class, 'partner_wallet_id'); }
    public function panel() { return $this->belongsTo(PartnerPanel::class, 'partner_panel_id'); }
    public function inquiry() { return $this->belongsTo(InquiryRequest::class, 'inquiry_request_id'); }
}
