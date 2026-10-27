<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryRequestLog extends Model
{
    use HasFactory;
    protected $fillable = ['inquiry_request_id', 'admin_id', 'event', 'message', 'payload'];
    protected $casts = ['payload' => 'array'];
    public function request() { return $this->belongsTo(InquiryRequest::class, 'inquiry_request_id'); }
}
