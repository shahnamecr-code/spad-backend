<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair the existing admin property dropdown source data in place.
 *
 * Keep current property slugs and stored advertisement values unchanged:
 *   floor -1 is ALREADY "همکف" on this installation.
 *   -2/-3/-4 are therefore newly allocated basement values.
 */
class FixSpadAdminFloorAndBuildYearOptions extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            $floor = DB::table('properties')->where('slug', 'floor')->lockForUpdate()->first();
            $year = DB::table('properties')->where('slug', 'age')->lockForUpdate()->first();

            if (!$floor || !$year || $floor->type !== 'select' || $year->type !== 'select') {
                throw new RuntimeException('SPAD_PROPERTY_DROPDOWN_TYPES_CHANGED');
            }

            $previousFloor = json_decode($floor->enum ?: 'null', true);
            $previousYears = json_decode($year->enum ?: 'null', true);
            if (!is_array($previousFloor) || !is_array($previousYears)) {
                throw new RuntimeException('SPAD_PROPERTY_DROPDOWN_ENUM_INVALID');
            }

            $previousFloor = array_map('strval', $previousFloor);
            $previousYears = array_map('strval', $previousYears);

            // Preserve every previously selectable stored value; never repurpose
            // -1 as basement because it currently represents "همکف".
            $knownFloor = array_merge(['بالاتر از 30'], array_map('strval', range(30, 1)), ['-1', '-2', '-3', '-4']);
            if (array_diff($previousFloor, $knownFloor)
                || array_diff(array_merge(['بالاتر از 30'], array_map('strval', range(30, 1)), ['-1']), $previousFloor)) {
                throw new RuntimeException('SPAD_FLOOR_ENUM_UNEXPECTED_ABORT');
            }
            $newFloor = array_merge(['بالاتر از 30'], array_map('strval', range(30, 1)), ['-1', '-4', '-3', '-2']);
            $newFloorLabels = array_merge(
                ['بالاتر از طبقه ۳۰'],
                array_map(function ($n) { return 'طبقه ' . $this->faDigits($n); }, range(30, 1)),
                ['همکف', 'زیرزمین ۳', 'زیرزمین ۲', 'زیرزمین ۱']
            );

            $knownYear = array_merge(['قبل از 1370'], array_map('strval', range(1370, 1405)));
            if (array_diff($previousYears, $knownYear)
                || array_diff(array_merge(['قبل از 1370'], array_map('strval', range(1370, 1401))), $previousYears)) {
                throw new RuntimeException('SPAD_BUILD_YEAR_ENUM_UNEXPECTED_ABORT');
            }

            // The admin component currently renders choices using unshift().
            // Store reversed presentation order so years show newest-first,
            // and floors show basement -> ground -> 1..30 in the dropdown.
            $newYears = $knownYear;

            DB::table('properties')->where('id', $floor->id)->update([
                'enum' => json_encode($newFloor, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'enumNames' => json_encode($newFloorLabels, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
            DB::table('properties')->where('id', $year->id)->update([
                'enum' => json_encode($newYears, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'enumNames' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

            echo 'SPAD_FLOOR_OPTIONS_REPAIRED=' . count($newFloor) . PHP_EOL;
            echo 'SPAD_FLOOR_GROUND_VALUE=-1' . PHP_EOL;
            echo 'SPAD_BUILD_YEAR_LAST=1405' . PHP_EOL;
            echo 'SPAD_BUILD_YEAR_OPTIONS_REPAIRED=' . count($newYears) . PHP_EOL;
        }, 3);
    }

    public function down()
    {
        // Do not revert in-place options: administrators may already have
        // selected a basement floor or construction year 1402..1405.
    }

    private function faDigits($number)
    {
        return strtr((string) $number, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
