<?php

namespace App\Services\Renovation;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PrivateRenovationImage
{
    public static function inspect(string $path, ?float $frameRatio = null): array
    {
        $bytes = Storage::disk('local')->get($path);
        $dimensions = @getimagesizefromstring($bytes);
        if (!$dimensions || $dimensions[0] * $dimensions[1] > 18000000) {
            throw new RuntimeException('invalid-renovation-image');
        }
        [$width, $height] = $dimensions;
        $source = @imagecreatefromstring($bytes);
        if (!$source) {
            throw new RuntimeException('invalid-renovation-image');
        }

        $cropWidth = $frameRatio ? min($width, (int) round($height * $frameRatio)) : $width;
        $cropHeight = $frameRatio ? min($height, (int) round($width / $frameRatio)) : $height;
        $scale = min(1, 900 / max($cropWidth, $cropHeight));
        $targetWidth = max(1, (int) round($cropWidth * $scale));
        $targetHeight = max(1, (int) round($cropHeight * $scale));
        $sample = imagecreatetruecolor($targetWidth, $targetHeight);
        $copied = $sample && imagecopyresampled(
            $sample, $source, 0, 0, (int) floor(($width - $cropWidth) / 2),
            (int) floor(($height - $cropHeight) / 2), $targetWidth, $targetHeight, $cropWidth, $cropHeight
        );
        imagedestroy($source);
        if (!$copied) {
            if ($sample) imagedestroy($sample);
            throw new RuntimeException('invalid-renovation-image');
        }

        ob_start();
        imagejpeg($sample, null, 84);
        $jpeg = ob_get_clean();
        imagedestroy($sample);
        if (!$jpeg) {
            throw new RuntimeException('invalid-renovation-image');
        }

        return [
            'jpeg' => $jpeg,
            'kind' => $width >= 1024 && $height >= 512 && abs($width / $height - 2) < 0.035
                ? 'panorama'
                : 'photo',
        ];
    }
}
