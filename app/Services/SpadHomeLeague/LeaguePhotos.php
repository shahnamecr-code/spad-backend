<?php

namespace App\Services\SpadHomeLeague;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeaguePhotos
{
    public function store(array $files): array
    {
        $paths = [];
        try {
            foreach ($files as $file) {
                $jpeg = $this->sanitizedJpeg($file);
                $path = 'private/spad-home-league/' . Str::uuid() . '.jpg';
                abort_unless(Storage::disk('local')->put($path, $jpeg), 503, 'ذخیرهٔ تصویر انجام نشد.');
                $paths[] = $path;
            }
            return $paths;
        } catch (Throwable $error) {
            $this->delete($paths);
            throw $error;
        }
    }

    public function delete(array $paths): void
    {
        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }

    public function response(string $path)
    {
        $storage = Storage::disk('local');
        abort_unless($storage->exists($path), 404);
        return $storage->response($path, 'home-league.jpg', [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function sanitizedJpeg(UploadedFile $file): string
    {
        abort_unless(function_exists('imagecreatefromstring') && function_exists('imagejpeg'), 503, 'پردازش امن تصویر در دسترس نیست.');
        $path = $file->getRealPath();
        $size = $path ? @getimagesize($path) : false;
        if (!$size || !in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 16000000) {
            throw ValidationException::withMessages(['photos' => 'فقط تصاویر معتبر JPEG، PNG و WebP تا ۱۶ مگاپیکسل پذیرفته می‌شوند.']);
        }

        $source = @imagecreatefromstring(file_get_contents($path));
        if (!$source) {
            throw ValidationException::withMessages(['photos' => 'خواندن یکی از تصاویر ممکن نبود.']);
        }

        try {
            $orientation = $size['mime'] === 'image/jpeg' && function_exists('exif_read_data')
                ? (int) (@exif_read_data($path)['Orientation'] ?? 1)
                : 1;
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($source, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            }
            $angle = [3 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $rotated = imagerotate($source, $angle, 0);
                if ($rotated) {
                    imagedestroy($source);
                    $source = $rotated;
                }
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, 1440 / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            abort_unless($target, 503, 'پردازش تصویر انجام نشد.');
            try {
                imagefill($target, 0, 0, imagecolorallocate($target, 250, 250, 250));
                imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                ob_start();
                try {
                    $encoded = imagejpeg($target, null, 83);
                    $jpeg = ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                abort_unless($encoded && $jpeg, 503, 'ساخت تصویر پاک‌سازی‌شده انجام نشد.');
                return $jpeg;
            } finally {
                imagedestroy($target);
            }
        } finally {
            imagedestroy($source);
        }
    }
}
