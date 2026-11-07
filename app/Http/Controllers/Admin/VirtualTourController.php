<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVirtualTourSceneImage;
use App\Models\Advertisement;
use App\Models\VirtualTour;
use App\Models\VirtualTourFloorplanPoint;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;
use App\Services\VirtualTour\VirtualTourAccess;
use App\Services\VirtualTour\VirtualTourImageProcessor;
use App\Services\VirtualTour\VirtualTourPresenter;
use App\Services\VirtualTour\VirtualTourPublishValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VirtualTourController extends Controller
{
    public function index(Request $request)
    {
        $query = VirtualTour::query()
            ->with(['advertisement.city', 'creator'])
            ->withCount(['scenes', 'hotspots'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('advertisement_id', $search);
            });
        }

        $items = $query->paginate(min(50, max(10, (int) $request->input('per_page', 20))));
        $items->getCollection()->transform(function ($tour) {
            return [
                'id' => $tour->id,
                'advertisement_id' => $tour->advertisement_id,
                'title' => $tour->title,
                'slug' => $tour->slug,
                'public_url' => rtrim(env('FRONTEND_URL', env('CLIENT_URL', 'https://spad.estate')), '/') . '/virtual-tour/' . $tour->slug,
                'status' => $tour->status,
                'model_ready' => !empty(data_get($tour->settings, 'model_path')),
                'scenes_count' => $tour->scenes_count,
                'hotspots_count' => $tour->hotspots_count,
                'views_count' => $tour->views_count,
                'cover' => (($coverScene = $tour->scenes()->whereNotNull('thumbnail_path')->first()) ? url('/api/admin/virtual-tours/assets/scenes/' . $coverScene->id . '/thumbnail') : null),
                'created_by' => $tour->creator ? (trim(($tour->creator->first_name ?? '') . ' ' . ($tour->creator->last_name ?? '')) ?: ('#' . $tour->creator->id)) : '—',
                'updated_at' => optional($tour->updated_at)->toIso8601String(),
            ];
        });
        return response()->json($items);
    }

    public function properties(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $query = Advertisement::query()
            ->with(['city', 'region'])
            ->select(['id', 'city_id', 'region_id', 'address', 'status', 'created_at'])
            ->orderByDesc('priority')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $items = $query->limit(30)->get()->map(function ($ad) {
            $tour = VirtualTour::where('advertisement_id', $ad->id)
                ->latest('updated_at')->first();

            return [
                'id' => $ad->id,
                'code' => '#' . $ad->id,
                'address' => $ad->address,
                'city' => optional($ad->city)->name,
                'region' => optional($ad->region)->name,
                'status' => $ad->status,
                'tour_id' => $tour?->id,
                'tour_status' => $tour?->status,
                'tour_title' => $tour?->title,
            ];
        });

        return response()->json(['status' => true, 'data' => $items]);
    }

    public function modelAsset(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $path = data_get($virtualTour->settings, 'model_path');
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$path || !str_starts_with($path, 'virtual-tours/' . $virtualTour->id . '/models/') || !$disk->exists($path)) abort(404);
        return response()->file($disk->path($path), [
            'Content-Type' => 'model/gltf-binary',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function uploadModel(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $request->validate(['model' => 'required|file|max:204800']);
        $file = $request->file('model');
        if (strtolower($file->getClientOriginalExtension()) !== 'glb') {
            return response()->json(['status' => false, 'message' => 'فقط مدل سه‌بعدی با فرمت GLB قابل قبول است.'], 422);
        }
        $handle = fopen($file->getRealPath(), 'rb');
        $header = $handle ? fread($handle, 12) : false;
        if ($handle) fclose($handle);
        if (!$header || strlen($header) !== 12 || substr($header, 0, 4) !== 'glTF' ||
            unpack('V', substr($header, 4, 4))[1] !== 2 ||
            unpack('V', substr($header, 8, 4))[1] !== $file->getSize()) {
            return response()->json(['status' => false, 'message' => 'ساختار فایل GLB معتبر نیست. خروجی glTF Binary نسخه ۲ تهیه کنید.'], 422);
        }
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        $path = $file->storeAs('virtual-tours/' . $virtualTour->id . '/models', Str::uuid() . '.glb', config('virtual-tour.disk', 'local'));
        if (!$path || !$disk->exists($path)) {
            return response()->json(['status' => false, 'message' => 'ذخیره مدل انجام نشد.'], 500);
        }
        $settings = $virtualTour->settings ?: [];
        $oldPath = $settings['model_path'] ?? null;
        $settings['model_path'] = $path;
        $settings['model_filename'] = mb_substr($file->getClientOriginalName(), 0, 191);
        $settings['model_bytes'] = $file->getSize();
        $virtualTour->update(['settings' => $settings]);
        if ($oldPath && $oldPath !== $path && str_starts_with($oldPath, 'virtual-tours/' . $virtualTour->id . '/models/')) {
            $disk->delete($oldPath);
        }
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function deleteModel(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        if ($virtualTour->status === 'published') {
            return response()->json(['status' => false, 'message' => 'برای حذف مدل ابتدا انتشار تور را متوقف کنید.'], 409);
        }
        $settings = $virtualTour->settings ?: [];
        $path = $settings['model_path'] ?? null;
        unset($settings['model_path'], $settings['model_filename'], $settings['model_bytes']);
        $virtualTour->update(['settings' => $settings]);
        if ($path && str_starts_with($path, 'virtual-tours/' . $virtualTour->id . '/models/')) {
            Storage::disk(config('virtual-tour.disk', 'local'))->delete($path);
        }
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function sceneAsset(VirtualTourScene $scene, $variant)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        if (!in_array($variant, ['ultra', 'high', 'medium', 'preview', 'thumbnail'], true)) abort(404);
        $column = $variant . '_path';
        $path = $scene->{$column};
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$path || !$disk->exists($path)) abort(404);
        return response()->file($disk->path($path), ['Cache-Control' => 'private, max-age=3600']);
    }

    public function floorplanAsset(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$virtualTour->floorplan_path || !$disk->exists($virtualTour->floorplan_path)) abort(404);
        return response()->file($disk->path($virtualTour->floorplan_path), ['Cache-Control' => 'private, max-age=3600']);
    }

    public function hotspotAsset(VirtualTourHotspot $hotspot)
    {
        $tour = $hotspot->scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$hotspot->media_path || !$disk->exists($hotspot->media_path)) abort(404);
        return response()->file($disk->path($hotspot->media_path), ['Cache-Control' => 'private, max-age=3600']);
    }

    public function forAdvertisement(Advertisement $advertisement)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $advertisement);
        $tour = VirtualTour::where('advertisement_id', $advertisement->id)
            ->orderByRaw("status = 'published' desc")
            ->latest('updated_at')
            ->first();
        return response()->json(['status' => true, 'data' => $tour ? VirtualTourPresenter::admin($tour) : null]);
    }

    public function ensureForAdvertisement(Request $request, Advertisement $advertisement)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $advertisement);

        $tour = VirtualTour::where('advertisement_id', $advertisement->id)
            ->orderByRaw("status = 'draft' desc")
            ->latest('id')
            ->first();

        if (!$tour) {
            $tour = VirtualTour::create([
                'advertisement_id' => $advertisement->id,
                'title' => 'تور مجازی ملک #' . $advertisement->id,
                'slug' => $this->uniqueSlug('virtual-tour-' . $advertisement->id),
                'status' => 'draft',
                'created_by' => auth('admin')->id(),
                'settings' => [
                    'auto_rotate' => false,
                    'auto_rotate_speed' => -2,
                    'show_floorplan' => true,
                    'show_thumbnails' => true,
                    'transition_ms' => 420,
                ],
            ]);
        }

        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour)]);
    }


    /**
     * Safely import a ZIP archive as panorama scenes. Do NOT extract archive
     * paths or host embedded HTML/JS: only verified equirectangular images
     * enter private virtual-tour storage.
     */
    public function importZipForAdvertisement(Request $request, Advertisement $advertisement, VirtualTourImageProcessor $processor)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $advertisement);
        $request->validate(['file' => 'required|file|max:81920']);
        $file = $request->file('file');
        if (!$file || strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return response()->json(['status' => false, 'message' => 'فایل انتخاب‌شده باید ZIP معتبر باشد.'], 422);
        }
        if (!class_exists(\ZipArchive::class)) {
            return response()->json(['status' => false, 'message' => 'افزونه ZIP روی سرور فعال نیست.'], 503);
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath(), \ZipArchive::CHECKCONS) !== true) {
            return response()->json(['status' => false, 'message' => 'فایل ZIP خراب یا نامعتبر است.'], 422);
        }
        $tempFiles = [];
        $maxImages = 20;
        $totalUnpacked = 0;
        $warnings = [];
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        $newPaths = [];
        $newIds = [];
        $tour = null;
        try {
            if ($zip->numFiles > 500) {
                return response()->json(['status' => false, 'message' => 'تعداد فایل‌های بسته بیش از حد مجاز است.'], 422);
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = $zip->getNameIndex($index);
                if (!$stat || !is_string($name) || str_ends_with($name, '/')) continue;
                // Ignore metadata and executable files; never write arbitrary ZIP paths.
                if (!preg_match('/\.(jpe?g|png|webp)$/i', $name)) continue;
                if (count($tempFiles) >= $maxImages) {
                    return response()->json(['status' => false, 'message' => 'در هر ZIP حداکثر ۲۰ تصویر پانوراما قابل واردکردن است.'], 422);
                }
                $size = (int) ($stat['size'] ?? 0);
                $compressed = (int) ($stat['comp_size'] ?? 0);
                if ($size < 1024 || $size > 40 * 1024 * 1024 ||
                    ($compressed > 0 && $size / $compressed > 150)) {
                    return response()->json(['status' => false, 'message' => 'یکی از تصاویر بسته بیش از حد بزرگ یا بیش از حد فشرده است.'], 422);
                }
                $totalUnpacked += $size;
                if ($totalUnpacked > 240 * 1024 * 1024) {
                    return response()->json(['status' => false, 'message' => 'حجم بازشده تصاویر از ۲۴۰ مگابایت بیشتر است.'], 422);
                }

                $stream = $zip->getStream($name);
                if (!$stream) {
                    return response()->json(['status' => false, 'message' => 'یکی از تصاویر ZIP قابل خواندن نیست.'], 422);
                }
                $tmp = tempnam(sys_get_temp_dir(), 'spad-vt-');
                if (!$tmp) { fclose($stream); throw new \RuntimeException('ساخت فایل موقت ممکن نشد.'); }
                $tempFiles[] = ['path' => $tmp, 'name' => $name, 'mime' => null];
                $out = fopen($tmp, 'wb');
                $written = 0;
                try {
                    while (!feof($stream)) {
                        $chunk = fread($stream, 1024 * 1024);
                        if ($chunk === false) throw new \RuntimeException('خواندن ZIP ناموفق بود.');
                        $written += strlen($chunk);
                        if ($written > 40 * 1024 * 1024 || $written > $size + 4096) {
                            throw new \RuntimeException('حجم بازشده تصویر نامعتبر است.');
                        }
                        if (fwrite($out, $chunk) !== strlen($chunk)) {
                            throw new \RuntimeException('نوشتن تصویر موقت ناموفق بود.');
                        }
                    }
                } finally {
                    fclose($stream);
                    fclose($out);
                }
                $info = @getimagesize($tmp);
                if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    throw new \RuntimeException('تصویر دارای فرمت نامعتبر است.');
                }
                $w = (int) $info[0];
                $h = (int) $info[1];
                if ($w < 1000 || $h < 500 || $w * $h > 45000000) {
                    throw new \RuntimeException('ابعاد تصویر پانوراما معتبر نیست.');
                }
                $ratio = $w / $h;
                if ($ratio < 1.85 || $ratio > 2.15) {
                    // Photos, thumbnails, cover images and cubemap tiles are not scenes.
                    @unlink($tmp);
                    array_pop($tempFiles);
                    continue;
                }
                $tempFiles[count($tempFiles) - 1]['mime'] = $info['mime'];
            }
            if (count($tempFiles) < 1) {
                return response()->json(['status' => false,
                    'message' => 'در ZIP هیچ تصویر پانورامای ۳۶۰ درجه با نسبت ۲ به ۱ پیدا نشد. فایل اصلی JPG یا WebP پانوراما باید داخل بسته باشد.'], 422);
            }

            // Do not mutate a published tour while importing new scenes.
            $tour = VirtualTour::where('advertisement_id', $advertisement->id)
                ->where('status', 'draft')->latest('id')->first();
            if (!$tour) {
                $tour = VirtualTour::create([
                    'advertisement_id' => $advertisement->id,
                    'title' => 'تور مجازی ملک #' . $advertisement->id,
                    'slug' => $this->uniqueSlug('virtual-tour-' . $advertisement->id),
                    'status' => 'draft',
                    'created_by' => auth('admin')->id(),
                    'settings' => ['auto_rotate' => false, 'show_thumbnails' => true, 'transition_ms' => 420],
                ]);
            }
            $nextOrder = ((int) $tour->scenes()->max('sort_order')) + 1;
            foreach ($tempFiles as $item) {
                $uuid = (string) Str::uuid();
                $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$item['mime']];
                $relative = 'virtual-tours/' . $tour->id . '/scenes/' . $uuid . '/original.' . $extension;
                $handle = fopen($item['path'], 'rb');
                try {
                    if (!$disk->put($relative, $handle)) throw new \RuntimeException('ذخیره تصویر تور ناموفق بود.');
                } finally {
                    fclose($handle);
                }
                $newPaths[] = 'virtual-tours/' . $tour->id . '/scenes/' . $uuid;
                $title = mb_substr(pathinfo(str_replace('\\', '/', $item['name']), PATHINFO_FILENAME), 0, 191);
                $scene = $tour->scenes()->create([
                    'uuid' => $uuid,
                    'title' => $title ?: 'فضای ' . $nextOrder,
                    'original_path' => $relative,
                    'sort_order' => $nextOrder++,
                    'process_status' => 'pending',
                    'metadata' => ['source' => 'zip_import'],
                ]);
                $newIds[] = $scene->id;
                if (config('virtual-tour.async_processing')) {
                    ProcessVirtualTourSceneImage::dispatch($scene->id)->onQueue('virtual-tour');
                } else {
                    $processor->process($scene);
                }
            }
            if (!$tour->start_scene_id) $tour->update(['start_scene_id' => $newIds[0]]);
            // Publication is a separate deliberate admin action.
            return response()->json([
                'status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh()),
                'imported_scenes' => count($newIds),
                'message' => config('virtual-tour.async_processing')
                    ? 'بسته استخراج شد؛ تصاویر در صف پردازش قرار گرفتند.'
                    : 'بسته استخراج و تصاویر پانوراما آماده شدند. پس از بررسی تور را منتشر کنید.',
            ]);
        } catch (\Throwable $e) {
            // Restore previous draft scenes on failed imports and remove their files.
            if ($newIds) VirtualTourScene::whereIn('id', $newIds)->delete();
            foreach ($newPaths as $prefix) $disk->deleteDirectory($prefix);
            \Log::warning('virtual_tour_zip_import_failed', [
                'advertisement_id' => $advertisement->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'استخراج تور ناموفق بود. ساختار ZIP و تصاویر پانوراما را بررسی کنید.'], 422);
        } finally {
            foreach ($tempFiles as $item) if (is_file($item['path'])) @unlink($item['path']);
            $zip->close();
        }
    }

    public function show(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour)]);
    }

    public function update(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:191',
            'start_scene_id' => 'nullable|integer',
            'settings' => 'nullable|array',
        ]);

        if (array_key_exists('start_scene_id', $data) && $data['start_scene_id']) {
            if (!$virtualTour->scenes()->whereKey($data['start_scene_id'])->exists()) {
                return response()->json(['status' => false, 'message' => 'Start Scene متعلق به این تور نیست.'], 422);
            }
        }
        if (array_key_exists('settings', $data)) {
            $existing = $virtualTour->settings ?: [];
            $allowed = ['spawn_x', 'spawn_y', 'spawn_z', 'heading'];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data['settings'])) {
                    $value = $data['settings'][$key];
                    if (!is_numeric($value) || !is_finite((float) $value) || abs((float) $value) > 10000) {
                        return response()->json(['status' => false, 'message' => 'موقعیت شروع یا جهت دوربین معتبر نیست.'], 422);
                    }
                    $existing[$key] = (float) $value;
                }
            }
            $data['settings'] = $existing; // Preserve server-owned model_path and model metadata.
        }
        $virtualTour->update($data);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function uploadScenes(Request $request, VirtualTour $virtualTour, VirtualTourImageProcessor $processor)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $maxKb = max(1024, (int) config('virtual-tour.max_upload_mb', 80) * 1024);
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . $maxKb,
            'titles' => 'nullable|array',
        ]);

        $created = [];
        $warnings = [];
        $nextOrder = (int) $virtualTour->scenes()->max('sort_order') + 1;
        foreach ($request->file('images') as $index => $file) {
            $size = @getimagesize($file->getRealPath());
            if (!$size || empty($size[0]) || empty($size[1])) {
                continue;
            }
            $ratio = $size[0] / $size[1];
            $ratioCfg = config('virtual-tour.ratio');
            if ($ratio < $ratioCfg['min'] || $ratio > $ratioCfg['max']) {
                return response()->json([
                    'status' => false,
                    'message' => 'تصویر «' . $file->getClientOriginalName() . '» نسبت مناسب Equirectangular نزدیک 2:1 ندارد.',
                ], 422);
            }

            $uuid = (string) Str::uuid();
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $path = $file->storeAs('virtual-tours/' . $virtualTour->id . '/scenes/' . $uuid, 'original.' . $ext, config('virtual-tour.disk', 'local'));
            $title = data_get($request->input('titles', []), $index);
            if (!$title) {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
            $metadata = [
                'original_name' => $file->getClientOriginalName(),
                'original_size' => $file->getSize(),
                'aspect_ratio' => round($ratio, 4),
                'aspect_warning' => $ratio < $ratioCfg['warning_min'] || $ratio > $ratioCfg['warning_max'],
            ];
            if ($metadata['aspect_warning']) {
                $warnings[] = 'تصویر «' . $title . '» نزدیک 2:1 است اما دقیقاً 2:1 نیست؛ نتیجه را Preview کنید.';
            }

            $scene = $virtualTour->scenes()->create([
                'uuid' => $uuid,
                'title' => mb_substr($title, 0, 191),
                'original_path' => $path,
                'sort_order' => $nextOrder++,
                'process_status' => 'pending',
                'metadata' => $metadata,
            ]);

            if (config('virtual-tour.async_processing')) {
                ProcessVirtualTourSceneImage::dispatch($scene->id)->onQueue('virtual-tour');
            } else {
                try {
                    $processor->process($scene);
                } catch (\Throwable $e) {
                    // Scene remains visible with failed status and can be reprocessed after server configuration is fixed.
                }
            }
            $created[] = $scene->fresh();
        }

        if (!$virtualTour->start_scene_id && count($created)) {
            $virtualTour->update(['start_scene_id' => $created[0]->id]);
        }

        return response()->json([
            'status' => true,
            'warnings' => $warnings,
            'data' => VirtualTourPresenter::admin($virtualTour->fresh()),
        ]);
    }

    public function reprocessScene(VirtualTourScene $scene, VirtualTourImageProcessor $processor)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        if (config('virtual-tour.async_processing')) {
            $scene->update(['process_status' => 'pending', 'process_error' => null]);
            ProcessVirtualTourSceneImage::dispatch($scene->id)->onQueue('virtual-tour');
        } else {
            $processor->process($scene);
        }
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function replaceSceneImage(Request $request, VirtualTourScene $scene, VirtualTourImageProcessor $processor)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $maxKb = max(1024, (int) config('virtual-tour.max_upload_mb', 80) * 1024);
        $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . $maxKb]);
        $file = $request->file('image');
        $size = @getimagesize($file->getRealPath());
        if (!$size || $size[1] < 1 || ($size[0] / $size[1]) < config('virtual-tour.ratio.min') || ($size[0] / $size[1]) > config('virtual-tour.ratio.max')) {
            return response()->json(['status' => false, 'message' => 'تصویر جدید باید پانورامای Equirectangular نزدیک 2:1 باشد.'], 422);
        }

        $this->deleteSceneVariants($scene, false);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs('virtual-tours/' . $tour->id . '/scenes/' . $scene->uuid, 'original.' . $ext, config('virtual-tour.disk', 'local'));
        $scene->update([
            'original_path' => $path,
            'ultra_path' => null, 'high_path' => null, 'medium_path' => null,
            'preview_path' => null, 'thumbnail_path' => null,
            'process_status' => 'pending', 'process_error' => null,
            'metadata' => ['original_name' => $file->getClientOriginalName(), 'original_size' => $file->getSize()],
        ]);
        if (config('virtual-tour.async_processing')) {
            ProcessVirtualTourSceneImage::dispatch($scene->id)->onQueue('virtual-tour');
        } else {
            try { $processor->process($scene); } catch (\Throwable $e) {}
        }
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function updateScene(Request $request, VirtualTourScene $scene)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:191',
            'initial_yaw' => 'sometimes|numeric|between:-180,180',
            'initial_pitch' => 'sometimes|numeric|between:-90,90',
            'initial_fov' => 'sometimes|numeric|between:35,120',
        ]);
        $scene->update($data);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function reorderScenes(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $data = $request->validate(['scene_ids' => 'required|array', 'scene_ids.*' => 'integer']);
        $owned = $virtualTour->scenes()->whereIn('id', $data['scene_ids'])->pluck('id')->all();
        if (count($owned) !== count(array_unique($data['scene_ids']))) {
            return response()->json(['status' => false, 'message' => 'ترتیب Sceneها معتبر نیست.'], 422);
        }
        foreach ($data['scene_ids'] as $order => $id) {
            VirtualTourScene::where('tour_id', $virtualTour->id)->whereKey($id)->update(['sort_order' => $order]);
        }
        return response()->json(['status' => true]);
    }

    /**
     * Create a non-destructive two-way walkthrough from the ordered 360 scenes.
     * The generated arrow positions are initial suggestions; the administrator
     * can position each hotspot precisely in the panorama editor afterwards.
     * Existing connections and manually positioned hotspots are never changed.
     */
    public function generateNavigation(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);

        if ($virtualTour->status === 'published') {
            return response()->json(['status' => false, 'message' => 'برای بازسازی مسیرها، ابتدا تور را از انتشار خارج کنید.'], 409);
        }

        $scenes = $virtualTour->scenes()->orderBy('sort_order')->orderBy('id')->get();
        if ($scenes->count() < 2) {
            return response()->json(['status' => false, 'message' => 'برای ساخت مسیر خودکار حداقل دو تصویر ۳۶۰ لازم است.'], 422);
        }
        if ($scenes->contains(function ($scene) { return $scene->process_status !== 'ready'; })) {
            return response()->json(['status' => false, 'message' => 'ابتدا پردازش همه تصاویر ۳۶۰ باید تکمیل شود.'], 422);
        }

        $created = DB::transaction(function () use ($virtualTour, $scenes) {
            if (!$virtualTour->start_scene_id || !$scenes->contains('id', $virtualTour->start_scene_id)) {
                $virtualTour->update(['start_scene_id' => $scenes->first()->id]);
            }
            $created = 0;
            for ($index = 0; $index < $scenes->count() - 1; $index++) {
                $pairs = [
                    [$scenes[$index], $scenes[$index + 1], false],
                    [$scenes[$index + 1], $scenes[$index], true],
                ];
                foreach ($pairs as $pair) {
                    list($from, $to, $return) = $pair;
                    $alreadyConnected = $from->hotspots()
                        ->where('type', 'navigation')
                        ->where('target_scene_id', $to->id)
                        ->exists();
                    if ($alreadyConnected) continue;
                    $yaw = (float) $from->initial_yaw + ($return ? 180.0 : 0.0);
                    if ($yaw > 180) $yaw -= 360;
                    $from->hotspots()->create([
                        'target_scene_id' => $to->id,
                        'type' => 'navigation',
                        'title' => ($return ? 'بازگشت به ' : 'رفتن به ') . $to->title,
                        'yaw' => $yaw,
                        'pitch' => -12,
                        'icon' => 'arrow',
                        'sort_order' => ((int) $from->hotspots()->max('sort_order')) + 1,
                        'settings' => ['spad_auto_route' => true, 'position_needs_review' => true],
                    ]);
                    $created++;
                }
            }
            return $created;
        });

        return response()->json([
            'status' => true,
            'created_links' => $created,
            'message' => $created ? 'مسیر دوطرفه با موفقیت ساخته شد؛ موقعیت فلش‌ها را در تصویر تنظیم کنید.' : 'همه مسیرهای متوالی از قبل ثبت شده‌اند.',
            'data' => VirtualTourPresenter::admin($virtualTour->fresh()),
        ]);
    }

    public function deleteScene(Request $request, VirtualTourScene $scene)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $incoming = $scene->incomingHotspots()->count();
        $outgoing = $scene->hotspots()->count();
        if (($incoming + $outgoing) > 0 && !$request->boolean('force')) {
            return response()->json([
                'status' => false,
                'requires_confirmation' => true,
                'message' => 'این Scene دارای ' . $incoming . ' اتصال ورودی و ' . $outgoing . ' Hotspot خروجی است. با حذف، ارتباط‌های مرتبط نیز پاک می‌شوند.',
            ], 409);
        }

        DB::transaction(function () use ($scene, $tour) {
            VirtualTourHotspot::where('target_scene_id', $scene->id)->delete();
            VirtualTourHotspot::where('scene_id', $scene->id)->delete();
            VirtualTourFloorplanPoint::where('scene_id', $scene->id)->delete();
            if ((int) $tour->start_scene_id === (int) $scene->id) {
                $tour->update(['start_scene_id' => null]);
            }
            $this->deleteSceneVariants($scene, true);
            $scene->delete();
        });
        $next = $tour->scenes()->orderBy('sort_order')->first();
        if (!$tour->start_scene_id && $next) {
            $tour->update(['start_scene_id' => $next->id]);
        }
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function createHotspot(Request $request, VirtualTourScene $scene)
    {
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $data = $this->validateHotspot($request, $scene);
        $createReturn = $request->boolean('create_return');
        $hotspot = $scene->hotspots()->create($data);

        if ($createReturn && $hotspot->type === 'navigation' && $hotspot->target_scene_id) {
            $target = VirtualTourScene::where('tour_id', $tour->id)->find($hotspot->target_scene_id);
            if ($target && !$target->hotspots()->where('type', 'navigation')->where('target_scene_id', $scene->id)->exists()) {
                $target->hotspots()->create([
                    'target_scene_id' => $scene->id,
                    'type' => 'navigation',
                    'title' => 'بازگشت به ' . $scene->title,
                    'yaw' => (float) $target->initial_yaw,
                    'pitch' => (float) $target->initial_pitch,
                    'icon' => 'arrow',
                    'settings' => ['auto_created_return' => true],
                ]);
            }
        }

        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function updateHotspot(Request $request, VirtualTourHotspot $hotspot)
    {
        $scene = $hotspot->scene;
        $tour = $scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $hotspot->update($this->validateHotspot($request, $scene));
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function deleteHotspot(VirtualTourHotspot $hotspot)
    {
        $tour = $hotspot->scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        if ($hotspot->media_path) Storage::disk(config('virtual-tour.disk', 'local'))->delete($hotspot->media_path);
        $hotspot->delete();
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function uploadHotspotMedia(Request $request, VirtualTourHotspot $hotspot)
    {
        $tour = $hotspot->scene->tour;
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $tour->advertisement);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm|max:51200']);
        if ($hotspot->media_path) Storage::disk(config('virtual-tour.disk', 'local'))->delete($hotspot->media_path);
        $file = $request->file('file');
        $path = $file->store('virtual-tours/' . $tour->id . '/hotspots', config('virtual-tour.disk', 'local'));
        $hotspot->update(['media_path' => $path]);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($tour->fresh())]);
    }

    public function uploadFloorplan(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $request->validate(['floorplan' => 'required|image|mimes:jpg,jpeg,png,webp|max:15360']);
        if ($virtualTour->floorplan_path) Storage::disk(config('virtual-tour.disk', 'local'))->delete($virtualTour->floorplan_path);
        $path = $request->file('floorplan')->store('virtual-tours/' . $virtualTour->id . '/floorplan', config('virtual-tour.disk', 'local'));
        $virtualTour->update(['floorplan_path' => $path]);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function deleteFloorplan(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        if ($virtualTour->floorplan_path) Storage::disk(config('virtual-tour.disk', 'local'))->delete($virtualTour->floorplan_path);
        $virtualTour->floorplanPoints()->delete();
        $virtualTour->update(['floorplan_path' => null]);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function saveFloorplanPoint(Request $request, VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $data = $request->validate([
            'scene_id' => 'required|integer',
            'x' => 'required|numeric|between:0,100',
            'y' => 'required|numeric|between:0,100',
            'label' => 'nullable|string|max:191',
        ]);
        if (!$virtualTour->scenes()->whereKey($data['scene_id'])->exists()) {
            return response()->json(['status' => false, 'message' => 'Scene معتبر نیست.'], 422);
        }
        $virtualTour->floorplanPoints()->updateOrCreate(['scene_id' => $data['scene_id']], $data);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function validatePublish(VirtualTour $virtualTour, VirtualTourPublishValidator $validator)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        return response()->json(array_merge(['status' => true], $validator->validateTour($virtualTour)));
    }

    public function publish(Request $request, VirtualTour $virtualTour, VirtualTourPublishValidator $validator)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $result = $validator->validateTour($virtualTour);
        if (count($result['errors'])) {
            return response()->json(['status' => false, 'message' => 'تور آماده انتشار نیست.', 'validation' => $result], 422);
        }
        if (count($result['warnings']) && !$request->boolean('accept_warnings')) {
            return response()->json(['status' => false, 'requires_confirmation' => true, 'validation' => $result], 409);
        }
        DB::transaction(function () use ($virtualTour) {
            VirtualTour::where('advertisement_id', $virtualTour->advertisement_id)
                ->where('id', '<>', $virtualTour->id)
                ->where('status', 'published')
                ->update(['status' => 'draft', 'published_at' => null]);
            $virtualTour->update(['status' => 'published', 'published_at' => now()]);
        });
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function unpublish(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $virtualTour->update(['status' => 'draft', 'published_at' => null]);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($virtualTour->fresh())]);
    }

    public function duplicate(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $copy = DB::transaction(function () use ($virtualTour) {
            $virtualTour->load(['scenes.hotspots', 'floorplanPoints']);
            $copy = $virtualTour->replicate(['slug', 'status', 'views_count', 'published_at', 'start_scene_id', 'floorplan_path']);
            $copy->title = $virtualTour->title . ' - کپی';
            $copy->slug = $this->uniqueSlug($virtualTour->slug . '-copy');
            $copy->status = 'draft';
            $copy->views_count = 0;
            $copy->published_at = null;
            $copy->created_by = auth('admin')->id();
            $copy->floorplan_path = null;
            $copy->save();

            if ($virtualTour->floorplan_path && Storage::disk(config('virtual-tour.disk', 'local'))->exists($virtualTour->floorplan_path)) {
                $ext = pathinfo($virtualTour->floorplan_path, PATHINFO_EXTENSION) ?: 'jpg';
                $floor = 'virtual-tours/' . $copy->id . '/floorplan/plan.' . $ext;
                Storage::disk(config('virtual-tour.disk', 'local'))->copy($virtualTour->floorplan_path, $floor);
                $copy->floorplan_path = $floor;
                $copy->save();
            }


            $originalModel = data_get($virtualTour->settings, 'model_path');
            $copySettings = $copy->settings ?: [];
            unset($copySettings['model_path'], $copySettings['model_filename'], $copySettings['model_bytes']);
            if ($originalModel && str_starts_with($originalModel, 'virtual-tours/' . $virtualTour->id . '/models/') &&
                Storage::disk(config('virtual-tour.disk', 'local'))->exists($originalModel)) {
                $copyModel = 'virtual-tours/' . $copy->id . '/models/' . Str::uuid() . '.glb';
                if (Storage::disk(config('virtual-tour.disk', 'local'))->copy($originalModel, $copyModel)) {
                    $copySettings['model_path'] = $copyModel;
                    $copySettings['model_filename'] = data_get($virtualTour->settings, 'model_filename');
                    $copySettings['model_bytes'] = data_get($virtualTour->settings, 'model_bytes');
                }
            }
            $copy->settings = $copySettings;
            $copy->save();

            $sceneMap = [];
            foreach ($virtualTour->scenes as $scene) {
                $newUuid = (string) Str::uuid();
                $newScene = $scene->replicate(['uuid', 'views_count']);
                $newScene->tour_id = $copy->id;
                $newScene->uuid = $newUuid;
                $newScene->views_count = 0;

                foreach (['original', 'ultra', 'high', 'medium', 'preview', 'thumbnail'] as $variant) {
                    $column = $variant . '_path';
                    $oldPath = $scene->{$column};
                    if ($oldPath && Storage::disk(config('virtual-tour.disk', 'local'))->exists($oldPath)) {
                        $name = basename($oldPath);
                        $newPath = 'virtual-tours/' . $copy->id . '/scenes/' . $newUuid . '/' . $name;
                        Storage::disk(config('virtual-tour.disk', 'local'))->copy($oldPath, $newPath);
                        $newScene->{$column} = $newPath;
                    }
                }
                $newScene->save();
                $sceneMap[$scene->id] = $newScene->id;
            }

            foreach ($virtualTour->scenes as $scene) {
                foreach ($scene->hotspots as $hotspot) {
                    $newHotspot = $hotspot->replicate(['clicks_count', 'media_path']);
                    $newHotspot->scene_id = $sceneMap[$scene->id];
                    $newHotspot->target_scene_id = $hotspot->target_scene_id ? ($sceneMap[$hotspot->target_scene_id] ?? null) : null;
                    $newHotspot->clicks_count = 0;
                    $newHotspot->media_path = null;
                    $newHotspot->save();
                    if ($hotspot->media_path && Storage::disk(config('virtual-tour.disk', 'local'))->exists($hotspot->media_path)) {
                        $name = $newHotspot->id . '-' . basename($hotspot->media_path);
                        $newPath = 'virtual-tours/' . $copy->id . '/hotspots/' . $name;
                        Storage::disk(config('virtual-tour.disk', 'local'))->copy($hotspot->media_path, $newPath);
                        $newHotspot->update(['media_path' => $newPath]);
                    }
                }
            }
            if ($virtualTour->start_scene_id && isset($sceneMap[$virtualTour->start_scene_id])) {
                $copy->start_scene_id = $sceneMap[$virtualTour->start_scene_id];
                $copy->save();
            }
            foreach ($virtualTour->floorplanPoints as $point) {
                if (isset($sceneMap[$point->scene_id])) {
                    $copy->floorplanPoints()->create([
                        'scene_id' => $sceneMap[$point->scene_id], 'x' => $point->x, 'y' => $point->y, 'label' => $point->label,
                    ]);
                }
            }
            return $copy;
        });
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::admin($copy)]);
    }

    public function destroy(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        Storage::disk(config('virtual-tour.disk', 'local'))->deleteDirectory('virtual-tours/' . $virtualTour->id);
        $virtualTour->delete();
        return response()->json(['status' => true]);
    }

    public function analytics(VirtualTour $virtualTour)
    {
        VirtualTourAccess::assertAdminCanManage(auth('admin')->user(), $virtualTour->advertisement);
        $sessions = $virtualTour->sessions();
        $sessionCount = (clone $sessions)->count();
        $avg = $sessionCount ? (int) round((clone $sessions)->avg('duration_seconds')) : 0;
        $sceneStats = $virtualTour->scenes()->select('id', 'title', 'views_count')->orderByDesc('views_count')->get();
        $hotspots = VirtualTourHotspot::whereIn('scene_id', $virtualTour->scenes()->pluck('id'))
            ->select('id', 'scene_id', 'title', 'type', 'clicks_count')->orderByDesc('clicks_count')->limit(30)->get();
        return response()->json(['status' => true, 'data' => [
            'views' => $virtualTour->views_count,
            'sessions' => $sessionCount,
            'average_viewing_seconds' => $avg,
            'scene_views' => $sceneStats,
            'hotspot_clicks' => $hotspots,
        ]]);
    }

    private function validateHotspot(Request $request, VirtualTourScene $scene)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['navigation', 'information', 'feature', 'photo', 'video', 'link'])],
            'title' => 'nullable|string|max:191',
            'content' => 'nullable|string|max:5000',
            'yaw' => 'required|numeric|between:-180,180',
            'pitch' => 'required|numeric|between:-90,90',
            'target_scene_id' => 'nullable|integer',
            'icon' => 'nullable|string|max:100',
            'link_url' => 'nullable|url|max:1000',
            'settings' => 'nullable|array',
        ]);
        if ($data['type'] === 'navigation') {
            if (empty($data['target_scene_id']) || !$scene->tour->scenes()->whereKey($data['target_scene_id'])->exists()) {
                throw new HttpResponseException(response()->json(['status' => false, 'message' => 'برای Hotspot ناوبری یک Scene مقصد معتبر انتخاب کنید.'], 422));
            }
            if ((int) $data['target_scene_id'] === (int) $scene->id) {
                throw new HttpResponseException(response()->json(['status' => false, 'message' => 'Scene مقصد نمی‌تواند همان Scene فعلی باشد.'], 422));
            }
        } else {
            $data['target_scene_id'] = null;
        }
        return $data;
    }

    private function deleteSceneVariants(VirtualTourScene $scene, $includeOriginal)
    {
        $paths = [$scene->ultra_path, $scene->high_path, $scene->medium_path, $scene->preview_path, $scene->thumbnail_path];
        if ($includeOriginal) $paths[] = $scene->original_path;
        Storage::disk(config('virtual-tour.disk', 'local'))->delete(array_values(array_filter($paths)));
        if ($includeOriginal) Storage::disk(config('virtual-tour.disk', 'local'))->deleteDirectory('virtual-tours/' . $scene->tour_id . '/scenes/' . $scene->uuid);
    }

    private function uniqueSlug($base)
    {
        $base = Str::slug($base) ?: 'virtual-tour';
        $slug = $base;
        $i = 2;
        while (VirtualTour::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
