<?php

namespace Tests\Unit;

use App\Services\SpadSunlight;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SpadSunlightTest extends TestCase
{
    public function test_solar_direction_matches_the_nrel_spa_reference_case(): void
    {
        // NREL/TP-560-34302, Appendix A example; geometric elevation excludes refraction.
        $time = new DateTimeImmutable('2003-10-17T12:30:30-07:00');
        $result = SpadSunlight::position($time, 39.742476, -105.1786);
        $this->assertEqualsWithDelta(194.34024, $result['azimuth_deg'], .03);
        $this->assertEqualsWithDelta(39.872, $result['elevation_deg'], .03);
    }

    public function test_tehran_conversion_apertures_and_obstacle_shadows(): void
    {
        $input = SpadSunlight::example();
        $with = SpadSunlight::analyze($input);
        $this->assertSame('2026-12-21T07:00:00+00:00', $with['solar']['utc']);
        $this->assertEqualsWithDelta(155.92, $with['solar']['azimuth_deg'], .03);
        $this->assertGreaterThan(0, $with['floor']['direct_m2']);
        $this->assertGreaterThan(0, $with['floor']['obstacle_shadow_m2']);
        $this->assertCount(24, $with['timeline']);

        $without = SpadSunlight::analyze(array_replace($input, ['obstacles' => []]));
        $this->assertEquals(0, $without['floor']['obstacle_shadow_m2']);
        $this->assertGreaterThan($with['floor']['direct_m2'], $without['floor']['direct_m2']);
        $this->assertEqualsWithDelta($without['floor']['direct_m2'], $with['floor']['direct_m2'] + $with['floor']['obstacle_shadow_m2'], .002);

        $night = SpadSunlight::analyze(array_replace($input, ['time' => '00:00']));
        $this->assertFalse($night['solar']['above_horizon']);
        $this->assertEquals(0, $night['floor']['direct_m2']);
        $this->assertSame(['version', 'kind', 'room', 'floor'], array_keys($with['export']));
        $this->assertSame(['width', 'depth'], array_keys($with['export']['room']));
    }

    public function test_window_must_fit_its_wall(): void
    {
        $input = SpadSunlight::example();
        $input['windows'][0]['offset'] = 12;
        $this->expectException(InvalidArgumentException::class);
        SpadSunlight::analyze($input);
    }
}
