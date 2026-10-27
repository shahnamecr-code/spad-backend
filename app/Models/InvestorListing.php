<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestorListing extends Model
{
    use HasFactory, SoftDeletes;

    const SOURCE_ADMIN = 'admin';
    const SOURCE_INVESTOR = 'investor';

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'advertisement_id', 'submitted_by_partner_panel_id', 'source', 'status',
        'market_price', 'investor_price', 'discount_percent', 'investment_score',
        'investment_horizon_months', 'deal_summary', 'risk_note', 'published_at',
        'expires_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'market_price' => 'integer',
        'investor_price' => 'integer',
        'discount_percent' => 'float',
        'investment_score' => 'integer',
        'investment_horizon_months' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function statuses()
    {
        return [
            self::STATUS_PENDING => 'در انتظار بررسی',
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_REJECTED => 'رد شده',
            self::STATUS_ARCHIVED => 'آرشیو شده',
        ];
    }

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function submittedByPanel()
    {
        return $this->belongsTo(PartnerPanel::class, 'submitted_by_partner_panel_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExclusive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACTIVE]);
    }

    public function getStatusLabelAttribute()
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function getCalculatedDiscountAttribute()
    {
        if ($this->discount_percent !== null) {
            return round((float)$this->discount_percent, 1);
        }
        $market = (int)$this->market_price;
        $investor = (int)$this->investor_price;
        if ($market <= 0 || $investor <= 0 || $investor >= $market) return null;
        return round((($market - $investor) / $market) * 100, 1);
    }

    public function getPriceGapAttribute()
    {
        $market = (int)$this->market_price;
        $investor = (int)$this->investor_price;
        return ($market > 0 && $investor > 0 && $market > $investor) ? $market - $investor : null;
    }
}
