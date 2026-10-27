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
