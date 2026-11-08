<?php

namespace App\Services\VirtualTour;

use App\Models\Advertisement;
use App\Models\VirtualTour;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class VirtualTourPackageImporter
{
    private const MAX_FILES = 2000;
    private const MAX_UNPACKED = 2147483648;
    private const MAX_SINGLE = 805306368;
    private const ALLOWED_EXTENSIONS = [
        'html', 'js', 'css', 'json', 'svg', 'png', 'jpg', 'jpeg',
        'webp', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'webm', 'mp4',
    ];

    /**
     * Preserve the finished standalone tour, rather than regenerating scenes.
     * All bytes stay on a private disk and can only be served by the published
     * package endpoint in a sandboxed, isolated iframe.
     */
    public function import(Advertisement $advertisement, UploadedFile $file, int $adminId): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip'
            || $file->getSize() > max(1, (int) config('virtual-tour.max_package_upload_mb', 512)) * 1024 * 1024
            || $file->getSize() < 1024) {
            throw new RuntimeException('فایل ZIP تور مجازی باید معتبر و حداکثر ۵۱۲ مگابایت باشد.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('افزونه ZIP روی سرور فعال نیست.');
        }
        $archive = new ZipArchive();
        if ($archive->open($file->getRealPath(), ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('فایل ZIP قابل بازکردن نیست یا آسیب دیده است.');
        }

        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        $tour = null;
        $created = false;
        $targetPrefix = null;
        try {
            if ($archive->numFiles > self::MAX_FILES) {
                throw new RuntimeException('تعداد فایل‌های داخل بسته بیش از حد مجاز است.');
            }

            $indexCandidates = [];
            $stats = [];
            $totalSize = 0;
            for ($i = 0; $i < $archive->numFiles; $i++) {
                $name = $archive->getNameIndex($i);
                $stat = $archive->statIndex($i);
                if (!is_string($name) || !$stat) throw new RuntimeException('فهرست ZIP معتبر نیست.');
                // Never normalize unsafe archive paths: reject them.
                $isDir = substr($name, -1) === '/';
                if (strpos($name, '\\') !== false || strpos($name, "\0") !== false ||
                    preg_match('~(^/|//|^\.|[[:cntrl:]]|:)~u', $name)) {
                    throw new RuntimeException('مسیر یکی از فایل‌های ZIP ناامن است.');
                }
                $segments = explode('/', rtrim($name, '/'));
                foreach ($segments as $segment) {
                    if ($segment === '' || $segment === '.' || $segment === '..' ||
                        strlen($segment) > 190 || $segment[0] === '.') {
                        throw new RuntimeException('نام یکی از فایل‌های بسته مجاز نیست.');
                    }
                }
                // Symlinks in ZIP would allow linking outside the package.
                $opsys = 0;
                $attributes = 0;
                if ($archive->getExternalAttributesIndex($i, $opsys, $attributes)) {
                    $mode = (((int) $attributes) >> 16) & 0170000;
                    if ($mode === 0120000) throw new RuntimeException('فایل پیوندی در ZIP مجاز نیست.');
                }
                if ($isDir) continue;

                $uncompressed = (int) ($stat['size'] ?? -1);
                $compressed = (int) ($stat['comp_size'] ?? -1);
                if ($uncompressed < 0 || $uncompressed > self::MAX_SINGLE ||
                    ($compressed < 1 && $uncompressed > 0) ||
                    ($compressed > 0 && $uncompressed / $compressed > 250)) {
                    throw new RuntimeException('اندازه فایل‌های ZIP معتبر نیست یا بسته بیش از حد فشرده شده است.');
                }
                $totalSize += $uncompressed;
                if ($totalSize > self::MAX_UNPACKED) {
                    throw new RuntimeException('حجم کل فایل‌های بازشده تور از سقف مجاز بیشتر است.');
                }
                $stats[$name] = ['size' => $uncompressed, 'index' => $i];
                if (basename($name) === 'index.html') $indexCandidates[] = $name;
            }
            if (!$indexCandidates) {
                throw new RuntimeException('این ZIP تور آماده نیست: فایل index.html پیدا نشد.');
            }
            usort($indexCandidates, function ($a, $b) {
                return substr_count($a, '/') <=> substr_count($b, '/') ?: strcmp($a, $b);
            });
            $entry = $indexCandidates[0];
            $sourceRoot = substr($entry, 0, -strlen('index.html'));
            $allowed = [];
            foreach ($stats as $name => $stat) {
                if ($sourceRoot !== '' && !str_starts_with($name, $sourceRoot)) continue;
                $relative = substr($name, strlen($sourceRoot));
                if ($relative === '' || strlen($relative) > 260) continue;
                $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
                if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) continue;
                if ($extension === 'html' && $relative !== 'index.html') continue;
                if (isset($allowed[$relative])) throw new RuntimeException('نام فایل تکراری در ZIP وجود دارد.');
                $allowed[$relative] = $stat;
            }

            if (!isset($allowed['index.html']) || count($allowed) < 4 ||
                $allowed['index.html']['size'] > 250000) {
                throw new RuntimeException('ساختار تور آماده ناقص است؛ فایل اصلی یا فایل‌های پخش وجود ندارند.');
            }
            $html = $archive->getFromIndex($allowed['index.html']['index']);
            if (!is_string($html) || !preg_match('~<!doctype html|<html\b~i', $html)) {
                throw new RuntimeException('صفحه اصلی تور معتبر نیست.');
            }
            // Keep old published tour available until the new package has been
            // fully copied and verified; never touch legacy scene records.
            $tour = VirtualTour::where('advertisement_id', $advertisement->id)
                ->orderByDesc('id')->first();
            if (!$tour) {
                $created = true;
                $tour = VirtualTour::create([
                    'advertisement_id' => $advertisement->id,
                    'title' => 'تور مجازی ملک ' . $advertisement->id,
                    'slug' => 'spad-ready-tour-' . $advertisement->id . '-' . strtolower(Str::random(7)),
                    'status' => 'draft',
                    'created_by' => $adminId,
                    'settings' => [],
                ]);
            }
            $version = (string) Str::uuid();
            $targetPrefix = 'virtual-tours/packages/' . $tour->id . '/' . $version;
            foreach ($allowed as $relative => $stat) {
                $sourceName = $sourceRoot . $relative;
                $stream = $archive->getStream($sourceName);
                if (!$stream) throw new RuntimeException('یکی از فایل‌های تور قابل خواندن نیست.');
                try {
                    $destination = $targetPrefix . '/' . $relative;
                    if (!$disk->put($destination, $stream)) {
                        throw new RuntimeException('ذخیره فایل تور در سرور ناموفق بود.');
                    }
                } finally {
                    fclose($stream);
                }
            }
            if (!$disk->exists($targetPrefix . '/index.html') ||
                $disk->size($targetPrefix . '/index.html') < 100) {
                throw new RuntimeException('فایل اصلی تور پس از استخراج پیدا نشد.');
            }
            $previous = $tour->settings ?: [];
            $oldPrefix = $previous['package_root'] ?? null;
            $settings = array_merge($previous, [
                'package_root' => $targetPrefix,
                'package_version' => $version,
                'package_entry' => 'index.html',
                'package_files' => array_keys($allowed),
                'package_ready' => true,
                'package_file_count' => count($allowed),
                'package_uploaded_at' => now()->toIso8601String(),
            ]);
            DB::transaction(function () use ($tour, $advertisement, $settings) {
                VirtualTour::where('advertisement_id', $advertisement->id)
                    ->where('id', '!=', $tour->id)
                    ->where('status', 'published')
                    ->update(['status' => 'draft']);
                $tour->update([
                    'settings' => $settings,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
            });
            // Do not delete old versions immediately; a visitor may be
            // mid-tour with the previous URL open.
            return [
                'status' => true,
                'message' => 'تور آماده به‌طور کامل نصب و منتشر شد؛ نیازی به استودیو یا پردازش دوباره نیست.',
                'file_count' => count($allowed),
                'tour_id' => $tour->id,
                'preview_url' => rtrim(env('FRONTEND_URL', 'https://spad.estate'), '/')
                    . '/virtual-tour/' . $tour->slug,
            ];
        } catch (\Throwable $error) {
            if ($targetPrefix) $disk->deleteDirectory($targetPrefix);
            if ($created && $tour) $tour->delete();
            Log::warning('virtual_tour_package_import_failed', [
                'advertisement_id' => $advertisement->id,
                'message' => $error->getMessage(),
            ]);
            if ($error instanceof RuntimeException) throw $error;
            throw new RuntimeException('خطا در ذخیره بسته تور روی سرور؛ بدون تغییر در تور قبلی.', 0, $error);
        } finally {
            $archive->close();
        }
    }
}
