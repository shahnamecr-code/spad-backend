<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleSetting extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'value'];

    public static function valueOf($key, $default = null)
    {
        $item = static::query()->where('key', $key)->first();
        return $item ? $item->value : $default;
    }

    public static function putValue($key, $value)
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
