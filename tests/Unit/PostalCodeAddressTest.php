<?php

namespace Tests\Unit;

use App\Services\PostalCodeAddress;
use PHPUnit\Framework\TestCase;

class PostalCodeAddressTest extends TestCase
{
    public function testDocumentedAddressRemainsCompatibleAndPrivateFieldsAreDropped(): void
    {
        $result = PostalCodeAddress::normalize([
            'province' => ' فارس ',
            'city' => 'شیراز',
            'town' => 'مرکزی',
            'floor' => '۰',
            'address' => 'نشانی آزمون',
            'lat' => 29.6,
            'long' => '52.5',
            'nationalCode' => 'sample-private-field',
        ]);

        $this->assertSame([
            'province' => 'فارس',
            'city' => 'شیراز',
            'town' => 'مرکزی',
            'floor' => '۰',
            'address' => 'نشانی آزمون',
            'lat' => 29.6,
            'long' => 52.5,
        ], $result);
    }

    public function testSingleStructuredAddressCanProvideCanonicalLocation(): void
    {
        $result = PostalCodeAddress::normalize([
            'Result' => [[
                'Address' => [
                    'Province_Name' => 'فارس',
                    'City' => ['nameFa' => 'شیراز'],
                    'Street' => 'خیابان نمونه',
                    'Latitude' => 29.6,
                    'Longitude' => 52.5,
                ],
            ]],
        ]);

        $this->assertSame('فارس', $result['province']);
        $this->assertSame('شیراز', $result['city']);
        $this->assertSame('خیابان نمونه', $result['street']);
        $this->assertSame(29.6, $result['lat']);
    }

    public function testAnUnspecifiedCityIsNeverGuessedFromTownOrFreeText(): void
    {
        foreach ([null, '', '   ', 'نا‌مشخص', 'N/A', 12, false] as $missing) {
            $result = PostalCodeAddress::normalize([
                'province' => 'فارس',
                'city' => $missing,
                'town' => 'شیراز',
                'address' => 'فارس، شیراز',
            ]);

            $this->assertSame('فارس', $result['province']);
            $this->assertArrayNotHasKey('city', $result);
        }
    }

    public function testMultipleAddressesAreNeverCombined(): void
    {
        $result = PostalCodeAddress::normalize([
            'data' => [
                ['province' => 'فارس'],
                ['city' => 'شیراز'],
            ],
        ]);

        $this->assertSame([], $result);
    }

    public function testIndependentWrappersDoNotSupplyDifferentHalvesOfALocation(): void
    {
        $result = PostalCodeAddress::normalize([
            'address' => ['province' => 'فارس'],
            'data' => ['city' => 'شیراز'],
        ]);

        $this->assertSame(['province' => 'فارس'], $result);
    }

    public function testCanonicalFieldsTakePrecedenceAndInvalidCoordinatesAreDiscarded(): void
    {
        $result = PostalCodeAddress::normalize([
            'City' => 'کازرون',
            'city' => 'شیراز',
            'province' => 'فارس',
            'data' => [
                'city' => 'مرودشت',
                'province' => 'یزد',
                'lat' => 51.2,
                'long' => 6.0,
            ],
        ]);

        $this->assertSame(['province' => 'فارس', 'city' => 'شیراز'], $result);
    }
}
