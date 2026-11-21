<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class SpadSunlight
{
    private const WALLS = ['front', 'right', 'back', 'left'];
    private const EPSILON = 1.0e-9;

    public static function example(): array
    {
        return [
            'latitude' => 29.59, 'longitude' => 52.58, 'date' => '2026-12-21', 'time' => '10:30',
            'room' => ['width' => 5.4, 'depth' => 4.2, 'height' => 2.85, 'orientation_deg' => 180],
            'windows' => [['wall' => 'front', 'offset' => 1.55, 'width' => 2.2, 'sill' => .65, 'height' => 1.8]],
            'obstacles' => [['wall' => 'front', 'offset' => .4, 'width' => 2.1, 'distance' => 3.1, 'depth' => 1.6, 'height' => 4.3]],
        ];
    }

    /** Apparent solar coordinates, with geometric rather than refracted elevation. */
    public static function position(DateTimeImmutable $date, float $latitude, float $longitude): array
    {
        $utc = $date->setTimezone(new DateTimeZone('UTC'));
        $century = ($utc->getTimestamp() / 86400 + 2440587.5 - 2451545) / 36525;
        $meanLongitude = self::angle(280.46646 + $century * (36000.76983 + .0003032 * $century));
        $meanAnomaly = deg2rad(357.52911 + $century * (35999.05029 - .0001537 * $century));
        $eccentricity = .016708634 - $century * (.000042037 + .0000001267 * $century);
        $center = sin($meanAnomaly) * (1.914602 - $century * (.004817 + .000014 * $century))
            + sin(2 * $meanAnomaly) * (.019993 - .000101 * $century) + sin(3 * $meanAnomaly) * .000289;
        $omega = deg2rad(125.04 - 1934.136 * $century);
        $apparentLongitude = deg2rad($meanLongitude + $center - .00569 - .00478 * sin($omega));
        $meanObliquity = 23 + (26 + (21.448 - $century * (46.815 + $century * (.00059 - $century * .001813))) / 60) / 60;
        $obliquity = deg2rad($meanObliquity + .00256 * cos($omega));
        $declination = asin(sin($obliquity) * sin($apparentLongitude));
        $y = tan($obliquity / 2) ** 2;
        $mean = deg2rad($meanLongitude);
        $equationMinutes = 4 * rad2deg($y * sin(2 * $mean) - 2 * $eccentricity * sin($meanAnomaly)
            + 4 * $eccentricity * $y * sin($meanAnomaly) * cos(2 * $mean)
            - .5 * $y * $y * sin(4 * $mean) - 1.25 * $eccentricity * $eccentricity * sin(2 * $meanAnomaly));

        $utcMinutes = 60 * (int) $utc->format('G') + (int) $utc->format('i') + (int) $utc->format('s') / 60;
        $hourAngle = deg2rad(self::angle(($utcMinutes + $equationMinutes + 4 * $longitude) / 4) - 180);
        $lat = deg2rad($latitude);
        $s = sin($lat) * sin($declination) + cos($lat) * cos($declination) * cos($hourAngle);
        $elevation = rad2deg(asin(max(-1, min(1, $s))));
        $azimuth = self::angle(rad2deg(atan2(sin($hourAngle), cos($hourAngle) * sin($lat) - tan($declination) * cos($lat))) + 180);
        return ['azimuth_deg' => $azimuth, 'elevation_deg' => $elevation, 'above_horizon' => $elevation > 0];
    }

    public static function analyze(array $input): array
    {
        $local = self::localTime($input['date'], $input['time']);
        $latitude = (float) $input['latitude'];
        $longitude = (float) $input['longitude'];
        $room = $input['room'];
        $windows = $input['windows'] ?? [];
        $obstacles = $input['obstacles'] ?? [];
        foreach ($windows as $index => $window) {
            $wallLength = in_array($window['wall'], ['front', 'back'], true) ? $room['width'] : $room['depth'];
            if ($window['offset'] + $window['width'] > $wallLength + self::EPSILON || $window['sill'] + $window['height'] > $room['height'] + self::EPSILON) {
                throw new InvalidArgumentException('پنجرهٔ ' . ($index + 1) . ' در ابعاد دیوار جا نمی‌گیرد.');
            }
        }
        $boxes = array_map(static function ($obstacle) use ($room) { return self::obstacleBox($obstacle, $room); }, $obstacles);
        $sun = self::position($local, $latitude, $longitude);
        $floor = self::floor($room, $windows, $boxes, $sun);
        $progress = [];
        $first = $last = null;
        $day = $local->setTime(0, 0);
        for ($minutes = 0; $minutes < 1440; $minutes += 5) {
            $moment = $day->setTime((int) ($minutes / 60), $minutes % 60);
            $step = self::position($moment, $latitude, $longitude);
            if ($step['above_horizon']) {
                $first = $first ?? $moment->format('H:i');
                $last = $moment->format('H:i');
            }
            if ($minutes % 60 === 0) {
                $progress[] = ['time' => $moment->format('H:i'), 'elevation_deg' => round($step['elevation_deg'], 2), 'azimuth_deg' => round($step['azimuth_deg'], 2)];
            }
        }
        $exposure = [];
        foreach ($windows as $index => $window) {
            $angle = (float) $room['orientation_deg'] + 90 * array_search($window['wall'], self::WALLS, true);
            $cosine = $sun['above_horizon'] ? max(0, cos(deg2rad($sun['azimuth_deg'] - $angle)) * cos(deg2rad($sun['elevation_deg']))) : 0;
            $exposure[] = ['index' => $index, 'wall' => $window['wall'], 'outside_incidence' => round($cosine, 4)];
        }
        return [
            'solar' => ['azimuth_deg' => round($sun['azimuth_deg'], 2), 'elevation_deg' => round($sun['elevation_deg'], 2),
                'above_horizon' => $sun['above_horizon'], 'timezone' => 'Asia/Tehran', 'local' => $local->format(DATE_ATOM),
                'utc' => $local->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
                'daylight_start_sample' => $first, 'daylight_end_sample' => $last],
            'floor' => $floor, 'timeline' => $progress, 'windows' => $exposure,
            'export' => ['version' => 1, 'kind' => 'geometric-direct-light', 'room' => ['width' => (float) $room['width'], 'depth' => (float) $room['depth']], 'floor' => $floor],
        ];
    }

    private static function localTime(string $date, string $time): DateTimeImmutable
    {
        $input = $date . ' ' . $time;
        $value = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $input, new DateTimeZone('Asia/Tehran'));
        if (!$value || $value->format('Y-m-d H:i') !== $input || $value->format('Y') < 1970 || $value->format('Y') > 2100) {
            throw new InvalidArgumentException('تاریخ و ساعت محلی ایران معتبر نیست. سال باید بین ۱۹۷۰ و ۲۱۰۰ باشد.');
        }
        return $value;
    }

    private static function floor(array $room, array $windows, array $obstacles, array $sun): array
    {
        $columns = max(8, min(48, (int) ceil(32 * $room['width'] / max($room['width'], $room['depth']))));
        $rows = max(8, min(48, (int) ceil(32 * $room['depth'] / max($room['width'], $room['depth']))));
        $cellWidth = $room['width'] / $columns;
        $cellDepth = $room['depth'] / $rows;
        $relative = deg2rad($sun['azimuth_deg'] - $room['orientation_deg']);
        $elevation = deg2rad($sun['elevation_deg']);
        $ray = [sin($relative) * cos($elevation), -cos($relative) * cos($elevation), sin($elevation)];
        $patterns = [];
        $lit = $shaded = 0;
        for ($row = 0; $row < $rows; $row++) {
            $pattern = '';
            for ($column = 0; $column < $columns; $column++) {
                $cell = 0;
                if ($sun['above_horizon'] && $windows) {
                    $x = ($column + .5) * $cellWidth;
                    $y = ($row + .5) * $cellDepth;
                    $cell = self::cell($x, $y, $ray, $room, $windows, $obstacles);
                }
                if ($cell === 1) $lit++;
                if ($cell === 2) $shaded++;
                $pattern .= (string) $cell;
            }
            $patterns[] = $pattern;
        }
        return ['columns' => $columns, 'rows' => $rows, 'cells' => $patterns, 'direct_m2' => round($lit * $cellWidth * $cellDepth, 3),
            'obstacle_shadow_m2' => round($shaded * $cellWidth * $cellDepth, 3), 'direct_percent' => round(100 * $lit / ($rows * $columns), 2)];
    }

    private static function cell(float $x, float $y, array $ray, array $room, array $windows, array $obstacles): int
    {
        [$dx, $dy, $dz] = $ray;
        $tx = abs($dx) <= self::EPSILON ? INF : (($dx > 0 ? $room['width'] : 0) - $x) / $dx;
        $ty = abs($dy) <= self::EPSILON ? INF : (($dy > 0 ? $room['depth'] : 0) - $y) / $dy;
        $exit = min($tx, $ty);
        if (!is_finite($exit) || $exit * $dz > $room['height']) return 0;
        $atX = $x + $dx * $exit;
        $atY = $y + $dy * $exit;
        $z = $dz * $exit;
        if ($tx < $ty) {
            $wall = $dx > 0 ? 'right' : 'left';
            $offset = $dx > 0 ? $atY : $room['depth'] - $atY;
        } else {
            $wall = $dy < 0 ? 'front' : 'back';
            $offset = $dy < 0 ? $atX : $room['width'] - $atX;
        }
        foreach ($windows as $window) {
            if ($wall !== $window['wall'] || $offset < $window['offset'] || $offset > $window['offset'] + $window['width']
                || $z < $window['sill'] || $z > $window['sill'] + $window['height']) continue;
            foreach ($obstacles as $obstacle) {
                if (self::intersectsBox([$x, $y, 0], $ray, $obstacle, $exit)) return 2;
            }
            return 1;
        }
        return 0;
    }

    private static function obstacleBox(array $obstacle, array $room): array
    {
        $a = $obstacle['offset'];
        $b = $a + $obstacle['width'];
        $near = $obstacle['distance'];
        $far = $near + $obstacle['depth'];
        $z = $obstacle['height'];
        switch ($obstacle['wall']) {
            case 'front': return [[$a, -$far, 0], [$b, -$near, $z]];
            case 'right': return [[$room['width'] + $near, $a, 0], [$room['width'] + $far, $b, $z]];
            case 'back': return [[$room['width'] - $b, $room['depth'] + $near, 0], [$room['width'] - $a, $room['depth'] + $far, $z]];
            default: return [[-$far, $room['depth'] - $b, 0], [-$near, $room['depth'] - $a, $z]];
        }
    }

    private static function intersectsBox(array $origin, array $ray, array $box, float $wallDistance): bool
    {
        $low = $wallDistance + self::EPSILON;
        $high = INF;
        for ($i = 0; $i < 3; $i++) {
            [$lower, $upper] = [$box[0][$i] - $origin[$i], $box[1][$i] - $origin[$i]];
            if (abs($ray[$i]) <= self::EPSILON) {
                if ($lower > 0 || $upper < 0) return false;
                continue;
            }
            $a = $lower / $ray[$i];
            $b = $upper / $ray[$i];
            $low = max($low, min($a, $b));
            $high = min($high, max($a, $b));
            if ($high < $low) return false;
        }
        return true;
    }

    private static function angle(float $angle): float
    {
        $result = fmod($angle, 360);
        return $result < 0 ? $result + 360 : $result;
    }
}
