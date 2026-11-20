<?php

namespace App\Services;

use InvalidArgumentException;

class SpadScanGeometry
{
    private const EPSILON = 0.000001;

    public static function prepare(array $rooms): array
    {
        $metrics = [];
        $normalized = [];
        foreach (array_values($rooms) as $index => $room) {
            try {
                [$value, $measurements] = self::prepareRoom($room);
                $normalized[] = $value;
                $metrics[] = $measurements;
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('اتاق ' . ($index + 1) . '، ' . $e->getMessage());
            }
        }

        return [$normalized, $metrics];
    }

    private static function prepareRoom(array $room): array
    {
        $points = array_map(static function ($point) {
            return ['x' => round((float) $point['x'], 4), 'y' => round((float) $point['y'], 4)];
        }, array_values($room['points']));
        $height = round((float) $room['height'], 4);
        $count = count($points);
        $twiceArea = 0.0;
        $lengths = [];

        for ($i = 0; $i < $count; $i++) {
            $a = $points[$i];
            $b = $points[($i + 1) % $count];
            $lengths[$i] = hypot($b['x'] - $a['x'], $b['y'] - $a['y']);
            if ($lengths[$i] < 0.05) {
                throw new InvalidArgumentException('دو گوشهٔ مجاور باید حداقل ۵ سانتی‌متر فاصله داشته باشند.');
            }
            $twiceArea += $a['x'] * $b['y'] - $b['x'] * $a['y'];
            for ($j = $i + 2; $j < $count; $j++) {
                if ($i === 0 && $j === $count - 1) {
                    continue;
                }
                if (self::intersects($a, $b, $points[$j], $points[($j + 1) % $count])) {
                    throw new InvalidArgumentException('ضلع‌های نقشه همدیگر را قطع می‌کنند. گوشه‌ها را به ترتیب دور اتاق وارد کنید.');
                }
            }
        }

        $area = abs($twiceArea) / 2;
        if ($area < 0.1 || $area > 10000) {
            throw new InvalidArgumentException('مساحت کف هر اتاق باید بین ۰٫۱ و ۱۰٬۰۰۰ متر مربع باشد.');
        }

        $openings = [];
        $openingArea = 0.0;
        foreach (array_values($room['openings'] ?? []) as $entry) {
            $opening = [
                'type' => $entry['type'], 'wall_index' => (int) $entry['wall_index'],
                'offset' => (float) $entry['offset'], 'width' => (float) $entry['width'],
                'height' => (float) $entry['height'], 'sill' => (float) $entry['sill'],
            ];
            $wall = $opening['wall_index'];
            if ($wall >= $count || $opening['offset'] + $opening['width'] > $lengths[$wall] + self::EPSILON
                || $opening['sill'] + $opening['height'] > $height + self::EPSILON) {
                throw new InvalidArgumentException('یکی از بازشوها از اندازهٔ دیوار انتخاب‌شده بزرگ‌تر است.');
            }
            foreach ($openings as $other) {
                if ($wall === $other['wall_index'] && self::overlaps($opening, $other, 'offset', 'width')
                    && self::overlaps($opening, $other, 'sill', 'height')) {
                    throw new InvalidArgumentException('دو بازشو روی یک دیوار هم‌پوشانی دارند.');
                }
            }
            $openingArea += $opening['width'] * $opening['height'];
            $openings[] = $opening;
        }

        $objects = [];
        foreach (array_values($room['objects'] ?? []) as $entry) {
            $object = ['name' => trim($entry['name'])];
            foreach (['x', 'y', 'width', 'depth', 'height'] as $field) {
                $object[$field] = (float) $entry[$field];
            }
            if (!self::objectFits($object, $points) || $object['height'] > $height + self::EPSILON) {
                throw new InvalidArgumentException('اندازه یا محل یکی از اشیا از محدودهٔ اتاق بیرون است.');
            }
            $objects[] = $object;
        }

        $name = trim($room['name']);
        $perimeter = array_sum($lengths);
        return [
            ['name' => $name, 'height' => $height, 'points' => $points, 'openings' => $openings, 'objects' => $objects],
            [
                'name' => $name, 'perimeter_m' => $perimeter, 'wall_lengths_m' => $lengths,
                'floor_area_m2' => $area, 'ceiling_area_m2' => $area,
                'wall_area_m2' => max(0, $perimeter * $height - $openingArea), 'openings_area_m2' => $openingArea,
            ],
        ];
    }

    private static function overlaps(array $a, array $b, string $start, string $length): bool
    {
        return min($a[$start] + $a[$length], $b[$start] + $b[$length])
            - max($a[$start], $b[$start]) > self::EPSILON;
    }

    private static function cross(array $a, array $b, array $c): float
    {
        return ($b['x'] - $a['x']) * ($c['y'] - $a['y']) - ($b['y'] - $a['y']) * ($c['x'] - $a['x']);
    }

    private static function onSegment(array $a, array $b, array $point): bool
    {
        return abs(self::cross($a, $b, $point)) <= self::EPSILON
            && $point['x'] >= min($a['x'], $b['x']) - self::EPSILON
            && $point['x'] <= max($a['x'], $b['x']) + self::EPSILON
            && $point['y'] >= min($a['y'], $b['y']) - self::EPSILON
            && $point['y'] <= max($a['y'], $b['y']) + self::EPSILON;
    }

    private static function intersects(array $a, array $b, array $c, array $d): bool
    {
        return self::crosses($a, $b, $c, $d)
            || self::onSegment($a, $b, $c) || self::onSegment($a, $b, $d)
            || self::onSegment($c, $d, $a) || self::onSegment($c, $d, $b);
    }

    private static function crosses(array $a, array $b, array $c, array $d): bool
    {
        $abC = self::cross($a, $b, $c);
        $abD = self::cross($a, $b, $d);
        $cdA = self::cross($c, $d, $a);
        $cdB = self::cross($c, $d, $b);
        return (($abC > self::EPSILON && $abD < -self::EPSILON) || ($abD > self::EPSILON && $abC < -self::EPSILON))
            && (($cdA > self::EPSILON && $cdB < -self::EPSILON) || ($cdB > self::EPSILON && $cdA < -self::EPSILON));
    }

    private static function objectFits(array $object, array $points): bool
    {
        $x = $object['x'];
        $y = $object['y'];
        $right = $x + $object['width'];
        $far = $y + $object['depth'];
        $box = [['x' => $x, 'y' => $y], ['x' => $right, 'y' => $y], ['x' => $right, 'y' => $far], ['x' => $x, 'y' => $far]];
        $size = count($points);
        foreach ($box as $i => $corner) {
            $next = $box[($i + 1) % 4];
            if (!self::inside($corner, $points) || !self::inside(['x' => ($corner['x'] + $next['x']) / 2, 'y' => ($corner['y'] + $next['y']) / 2], $points)) {
                return false;
            }
            for ($side = 0; $side < $size; $side++) {
                if (self::crosses($corner, $next, $points[$side], $points[($side + 1) % $size])) return false;
            }
        }
        return true;
    }

    private static function inside(array $point, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);
        for ($i = 0; $i < $count; $i++) {
            $a = $polygon[$i];
            $b = $polygon[($i + 1) % $count];
            if (self::onSegment($a, $b, $point)) return true;
            if (($a['y'] > $point['y']) !== ($b['y'] > $point['y']) &&
                $point['x'] < ($b['x'] - $a['x']) * ($point['y'] - $a['y']) / ($b['y'] - $a['y']) + $a['x']) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
