<?php

namespace App\Services\HomeDoctor;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PrivatePhoto
{
    public static function disk()
    {
        return Storage::disk('local');
    }

    public static function process(UploadedFile $photo): array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            abort(503, 'پردازش امن عکس موقتاً در دسترس نیست. می‌توانید درخواست متنی ثبت کنید.');
        }

        $source = $photo->getRealPath();
        $size = $source ? @getimagesize($source) : false;
        if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 14000000 ||
            !in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['image' => 'عکس معتبر JPG، PNG یا WebP با حداکثر ۱۴ مگاپیکسل انتخاب کنید.']);
        }

        $bytes = file_get_contents($source);
        $image = $bytes === false ? false : @imagecreatefromstring($bytes);
        if (!$image) {
            throw ValidationException::withMessages(['image' => 'خواندن این عکس ممکن نبود. فایل دیگری انتخاب کنید.']);
        }

        try {
            // Preserve the visual orientation while re-encoding away EXIF and GPS.
            $orientation = $size['mime'] === 'image/jpeg' && function_exists('exif_read_data')
                ? (int) (@exif_read_data($source)['Orientation'] ?? 1)
                : 1;
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            }
            $angle = [3 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientation] ?? 0;
            if ($angle && ($rotated = imagerotate($image, $angle, 0))) {
                imagedestroy($image);
                $image = $rotated;
            }

            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $scale = min(1, 1440 / max($sourceWidth, $sourceHeight));
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));
            $resized = imagecreatetruecolor($width, $height);
            abort_unless($resized, 503, 'آماده‌سازی عکس انجام نشد.');
            try {
                imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
                ob_start();
                try {
                    $saved = imagejpeg($resized, null, 84);
                    $jpeg = ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            } finally {
                imagedestroy($resized);
            }
            abort_unless($saved && is_string($jpeg) && $jpeg !== '', 503, 'آماده‌سازی عکس انجام نشد.');
            return [$jpeg, $width, $height];
        } finally {
            imagedestroy($image);
        }
    }
}
