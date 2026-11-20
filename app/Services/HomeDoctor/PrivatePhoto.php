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
        if (!function_exists('imagecreatefromstring')) {
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

        // Re-encoding removes EXIF, GPS and arbitrary extra data from the input file.
        // Bound the size of the retained image and the provider request.
        $scale = min(1, 1440 / max($size[0], $size[1]));
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        $resized = imagecreatetruecolor($width, $height);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        ob_start();
        try {
            $saved = imagejpeg($resized, null, 84);
            $jpeg = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($resized);
            imagedestroy($image);
        }
        abort_unless($saved && is_string($jpeg) && $jpeg !== '', 503, 'آماده‌سازی عکس انجام نشد.');
        return [$jpeg, $width, $height];
    }
}
