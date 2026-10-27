<?php

namespace App\Support;

use App\Models\City;

class SpadLocation
{
    public const CITY_NAME = 'شیراز';

    public static function city(): City
    {
        return City::query()
            ->where('name', self::CITY_NAME)
            ->firstOrFail();
    }

    public static function cityId(): int
    {
        return (int) self::city()->id;
    }

    public static function provinceId(): int
    {
        return (int) self::city()->province_id;
    }
}
