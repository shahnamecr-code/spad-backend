<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryBatch extends Model
{
    protected $fillable = ['user_id', 'idempotency_key', 'total_amount', 'status'];
    protected $casts = ['total_amount' => 'integer'];

    public function requests()
    {
        return $this->hasMany(InquiryRequest::class, 'inquiry_batch_id');
    }
}
