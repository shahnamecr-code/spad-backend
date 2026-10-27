<?php

namespace App\Services\VirtualTour;

use App\Models\VirtualTourScene;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VirtualTourImageProcessor
{
    public function process(VirtualTourScene $scene)
    {
        $scene->update(['process_status' => 'processing', 'process_error' => null]);

        try {
            $disk = Storage::disk(config('virtual-tour.disk', 'local'));
            $source = $disk->path($scene->original_path);
            $info = @getimagesize($source);
            if (!$info || empty($info[0]) || empty($info[1])) {
                throw new RuntimeException('ابعاد تصویر قابل تشخیص نیست.');
            }

            $width = (int) $info[0];
            $height = (int) $info[1];
            if (!extension_loaded('imagick') && ($width * $height) > 40000000) {
                throw new RuntimeException('برای تصاویر بسیار بزرگ 360 (بیش از 40MP) افزونه Imagick روی PHP لازم است؛ استفاده از GD ممکن است حافظه سرور را بیش از حد مصرف کند.');
            }
            $ratio = $height > 0 ? $width / $height : 0;
            $cfg = config('virtual-tour.ratio');
            if ($ratio < $cfg['min'] || $ratio > $cfg['max']) {
                throw new RuntimeException('نسبت تصویر برای پانورامای Equirectangular مناسب نیست.');
            }

            $variants = [];
            foreach (config('virtual-tour.variant_widths') as $name => $targetWidth) {
                $targetWidth = min((int) $targetWidth, $width);
                $path = $this->variantPath($scene, $name);
                $this->resize($source, Storage::disk(config('virtual-tour.disk', 'local'))->path($path), $targetWidth, $info['mime']);
                $variants[$name . '_path'] = $path;
            }

            $metadata = $scene->metadata ?: [];
            $metadata['aspect_ratio'] = round($ratio, 4);
            $metadata['aspect_warning'] = $ratio < $cfg['warning_min'] || $ratio > $cfg['warning_max'];
            $metadata['source_mime'] = $info['mime'];
            $metadata['processor'] = extension_loaded('imagick') ? 'imagick' : 'gd';

            $scene->update(array_merge($variants, [
                'width' => $width,
                'height' => $height,
                'metadata' => $metadata,
                'process_status' => 'ready',
                'process_error' => null,
            ]));
        } catch (\Throwable $e) {
            $scene->update([
                'process_status' => 'failed',
                'process_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            throw $e;
        }
    }

    private function variantPath(VirtualTourScene $scene, $name)
    {
        return 'virtual-tours/' . $scene->tour_id . '/scenes/' . $scene->uuid . '/' . $name . '.webp';
    }

    private function resize($source, $destination, $targetWidth, $mime)
    {
        $dir = dirname($destination);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('ساخت پوشه خروجی تصویر ممکن نشد.');
        }

        if (extension_loaded('imagick')) {
            $image = new \Imagick($source);
            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            }
            $image->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            if ($image->getImageWidth() > $targetWidth) {
                $image->resizeImage($targetWidth, 0, \Imagick::FILTER_LANCZOS, 0.9, true);
            }
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality((int) config('virtual-tour.webp_quality', 84));
            $image->stripImage();
            if (!$image->writeImage($destination)) {
                throw new RuntimeException('ذخیره نسخه بهینه تصویر ممکن نشد.');
            }
            $image->clear();
            $image->destroy();
            return;
        }

        if (!function_exists('imagewebp')) {
            throw new RuntimeException('برای پردازش تصاویر 360 افزونه Imagick یا GD+WebP روی PHP لازم است.');
        }

        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($source);
        } elseif ($mime === 'image/png') {
            $src = imagecreatefrompng($source);
        } elseif ($mime === 'image/webp') {
            $src = imagecreatefromwebp($source);
        } else {
            throw new RuntimeException('فرمت تصویر پشتیبانی نمی‌شود.');
        }

        if (!$src) {
            throw new RuntimeException('باز کردن تصویر برای پردازش ممکن نشد.');
        }
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $targetWidth = min($targetWidth, $srcW);
        $targetHeight = max(1, (int) round($srcH * ($targetWidth / $srcW)));
        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcW, $srcH);
        imagewebp($dst, $destination, (int) config('virtual-tour.webp_quality', 84));
        imagedestroy($src);
        imagedestroy($dst);
    }
}
