<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerPanel extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_NOTARY_OFFICE = 'notary_office';
    const TYPE_DEVELOPER = 'developer';
    const TYPE_ENGINEERING_ORGANIZATION = 'engineering_organization';
    const TYPE_INVESTOR = 'investor';
    const TYPE_BUILDING_MANAGER = 'building_manager';

    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'title',
        'organization_name',
        'registration_number',
        'notes',
        'settings',
        'created_by',
        'updated_by',
        'activated_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'activated_at' => 'datetime',
    ];

    public static function types()
    {
        return [
            self::TYPE_NOTARY_OFFICE => 'دفاتر اسناد رسمی',
            self::TYPE_DEVELOPER => 'سازندگان',
            self::TYPE_ENGINEERING_ORGANIZATION => 'دفاتر نظام مهندسی',
            self::TYPE_INVESTOR => 'سرمایه‌گذاران',
            self::TYPE_BUILDING_MANAGER => 'مدیر ساختمان',
        ];
    }

    public static function statuses()
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_SUSPENDED => 'تعلیق شده',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function assignedInquiryRequests()
    {
        return $this->hasMany(InquiryRequest::class, 'assigned_partner_panel_id');
    }

    public function wallet()
    {
        return $this->hasOne(PartnerWallet::class, 'partner_panel_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(PartnerWalletTransaction::class, 'partner_panel_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getTypeLabelAttribute()
    {
        return static::types()[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute()
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function publicPayload()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'title' => $this->title,
            'organization_name' => $this->organization_name,
            'registration_number' => $this->registration_number,
            'activated_at' => optional($this->activated_at)->toIso8601String(),
        ];
    }
}
