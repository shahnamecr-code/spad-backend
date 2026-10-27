<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerWallet extends Model
{
    use HasFactory;

    protected $fillable = ['partner_panel_id', 'balance', 'status'];
    protected $casts = ['balance' => 'integer'];

    public function panel() { return $this->belongsTo(PartnerPanel::class, 'partner_panel_id'); }
    public function transactions() { return $this->hasMany(PartnerWalletTransaction::class); }
}
