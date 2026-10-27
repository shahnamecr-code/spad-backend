<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'inquiry_service_id', 'reference', 'amount', 'partner_payout_amount', 'payment_method', 'payment_status', 'status',
        'gateway_id', 'gateway_amount', 'gateway_currency', 'transaction_id', 'callback_token',
        'gateway_reference_id', 'wallet_transaction_id', 'partner_payout_transaction_id', 'assigned_admin_id', 'assigned_partner_panel_id', 'input_data', 'result_data',
        'result_text', 'result_file_path', 'result_file_name', 'result_file_mime', 'result_file_size', 'operator_note', 'partner_note', 'correction_note', 'failure_message', 'paid_at', 'partner_assigned_at', 'partner_accepted_at', 'correction_requested_at', 'correction_resubmitted_at', 'completed_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'partner_payout_amount' => 'integer',
        'result_file_size' => 'integer',
        'gateway_amount' => 'integer',
        'input_data' => 'array',
        'result_data' => 'array',
        'paid_at' => 'datetime',
        'partner_assigned_at' => 'datetime',
        'partner_accepted_at' => 'datetime',
        'correction_requested_at' => 'datetime',
        'correction_resubmitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function service() { return $this->belongsTo(InquiryService::class, 'inquiry_service_id'); }
    public function gateway() { return $this->belongsTo(WalletGateway::class, 'gateway_id'); }
    public function operator() { return $this->belongsTo(Admin::class, 'assigned_admin_id'); }
    public function assignedPartner() { return $this->belongsTo(PartnerPanel::class, 'assigned_partner_panel_id'); }
    public function logs() { return $this->hasMany(InquiryRequestLog::class); }
    public function partnerPayoutTransaction() { return $this->belongsTo(PartnerWalletTransaction::class, 'partner_payout_transaction_id'); }
}
