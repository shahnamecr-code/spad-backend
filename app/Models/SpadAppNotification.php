<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpadAppNotification extends Model
{
    use SoftDeletes;

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_USER = 'user';

    protected $table = 'spad_app_notifications';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id')->withTrashed();
    }

    public function revoker()
    {
        return $this->belongsTo(Admin::class, 'revoked_by_admin_id')->withTrashed();
    }
}

