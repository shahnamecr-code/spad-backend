<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function valueOf($key, $default = null)
    {
        $value = static::query()->where('key', $key)->value('value');
        return $value === null ? $default : $value;
    }

    public static function putValue($key, $value)
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
