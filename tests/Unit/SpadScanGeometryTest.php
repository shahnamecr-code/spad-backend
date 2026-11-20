<?php

namespace Tests\Unit;

use App\Services\SpadScanGeometry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SpadScanGeometryTest extends TestCase
{
    public function test_concave_floor_and_wall_area_are_measured_from_vertices_and_openings(): void
    {
        [$rooms, $metrics] = SpadScanGeometry::prepare([[
            'name' => 'پذیرایی', 'height' => 3,
            'points' => $this->points([[0, 0], [5, 0], [5, 2], [3, 2], [3, 4], [0, 4]]),
            'openings' => [
                ['type' => 'door', 'wall_index' => 0, 'offset' => 0.5, 'width' => 1, 'height' => 2, 'sill' => 0],
                ['type' => 'window', 'wall_index' => 0, 'offset' => 2, 'width' => 1.5, 'height' => 1, 'sill' => 1],
            ],
            'objects' => [['name' => 'کمد', 'x' => .25, 'y' => .5, 'width' => 1.5, 'depth' => .5, 'height' => 2]],
        ]]);

        $this->assertEquals([5, 2, 2, 2, 3, 4], $metrics[0]['wall_lengths_m']);
        $this->assertEquals(16, $metrics[0]['floor_area_m2']);
        $this->assertEquals(16, $metrics[0]['ceiling_area_m2']);
        $this->assertEquals(3.5, $metrics[0]['openings_area_m2']);
        $this->assertEquals(50.5, $metrics[0]['wall_area_m2']);
        $this->assertSame('کمد', $rooms[0]['objects'][0]['name']);
    }

    public function test_self_intersection_and_out_of_wall_openings_fail_validation(): void
    {
        $this->invalid(['name' => 'اتاق', 'height' => 3, 'points' => $this->points([[0, 0], [4, 4], [4, 0], [0, 4]])], 'ضلع‌های نقشه');
        $this->invalid([
            'name' => 'اتاق', 'height' => 3, 'points' => $this->points([[0, 0], [4, 0], [4, 4], [0, 4]]),
            'openings' => [['type' => 'door', 'wall_index' => 0, 'offset' => 3.5, 'width' => 1, 'height' => 2, 'sill' => 0]],
        ], 'اندازهٔ دیوار');
    }

    public function test_overlapping_openings_and_an_object_outside_the_polygon_are_rejected(): void
    {
        $base = ['name' => 'اتاق', 'height' => 3, 'points' => $this->points([[0, 0], [4, 0], [4, 4], [0, 4]])];
        $this->invalid($base + ['openings' => [
            ['type' => 'door', 'wall_index' => 0, 'offset' => .25, 'width' => 1, 'height' => 2, 'sill' => 0],
            ['type' => 'window', 'wall_index' => 0, 'offset' => .75, 'width' => 1, 'height' => 1, 'sill' => 1],
        ]], 'هم‌پوشانی');
        $this->invalid($base + ['objects' => [['name' => 'میز', 'x' => 3.8, 'y' => 2, 'width' => 1, 'depth' => 1, 'height' => 1]]], 'محدودهٔ اتاق');
    }

    private function invalid(array $room, string $message): void
    {
        try {
            SpadScanGeometry::prepare([$room]);
            $this->fail('Invalid scan geometry was accepted');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString($message, $error->getMessage());
        }
    }

    private function points(array $coordinates): array
    {
        return array_map(static function ($point) { return ['x' => $point[0], 'y' => $point[1]]; }, $coordinates);
    }
}
