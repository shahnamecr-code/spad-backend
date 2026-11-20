<?php

namespace App\Http\Controllers;

use App\Services\SpadScanGeometry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class SpadScanController extends Controller
{
    private const MAX_SCANS = 50;
    private const MAX_PHOTOS = 12;
    private const MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const NO_CACHE = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

    public function index(Request $request)
    {
        $rows = $this->ownerScans($request)->orderByDesc('updated_at')->orderByDesc('id')
            ->get(['id', 'title', 'room_count', 'photo_count', 'updated_at']);
        return $this->data($rows);
    }

    public function show(Request $request, int $id)
    {
        return $this->data($this->details($this->owned($request, $id)));
    }

    public function store(Request $request)
    {
        $values = $this->geometryInput($request, true);
        return $this->data(DB::transaction(function () use ($request, $values) {
            $userId = $this->userId($request);
            $request->user()->newQuery()->whereKey($userId)->lockForUpdate()->firstOrFail();
            if ($this->ownerScans($request)->count() >= self::MAX_SCANS) {
                throw ValidationException::withMessages(['title' => 'حداکثر ۵۰ نقشه می‌توانید ذخیره کنید.']);
            }
            $id = DB::table('spad_scans')->insertGetId($this->row($values) + ['user_id' => $userId, 'created_at' => now()]);
            return $this->details($this->owned($request, $id));
        }), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->owned($request, $id);
        $values = $this->geometryInput($request, true);
        return $this->data(DB::transaction(function () use ($request, $id, $values) {
            $this->owned($request, $id, true);
            $this->ownerScans($request)->where('id', $id)->update($this->row($values));
            return $this->details($this->owned($request, $id));
        }));
    }

    public function destroy(Request $request, int $id)
    {
        $paths = DB::transaction(function () use ($request, $id) {
            $this->owned($request, $id, true);
            $paths = DB::table('spad_scan_photos')->where('scan_id', $id)->pluck('path')->all();
            $this->ownerScans($request)->where('id', $id)->delete();
            return $paths;
        });
        if ($paths) $this->deleteFiles($paths);
        return response()->noContent(204, self::NO_CACHE);
    }

    public function upload(Request $request, int $id)
    {
        $this->owned($request, $id);
        $request->validate(['photo' => 'required|file|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=10000,max_height=10000']);
        $file = $request->file('photo');
        $mime = $file->getMimeType();
        if (!isset(self::MIME[$mime])) {
            throw ValidationException::withMessages(['photo' => 'قالب عکس باید JPEG، PNG یا WebP باشد.']);
        }
        $path = null;
        try {
            $details = DB::transaction(function () use ($request, $id, $file, $mime, &$path) {
                $scan = $this->owned($request, $id, true);
                if ($scan->photo_count >= self::MAX_PHOTOS) {
                    throw ValidationException::withMessages(['photo' => 'حداکثر ۱۲ عکس برای هر نقشه مجاز است.']);
                }
                $filename = Str::uuid() . '.' . self::MIME[$mime];
                $directory = 'spad-scan/' . $id;
                $path = $directory . '/' . $filename;
                if (!Storage::disk('local')->putFileAs($directory, $file, $filename, 'private')) {
                    abort(503, 'ذخیرهٔ عکس در حال حاضر ممکن نیست.');
                }
                $name = Str::limit(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $file->getClientOriginalName()) ?? ''), 140, '');
                DB::table('spad_scan_photos')->insert([
                    'scan_id' => $id, 'name' => $name ?: 'عکس ملک', 'path' => $path,
                    'mime_type' => $mime, 'size_bytes' => $file->getSize(), 'created_at' => now(),
                ]);
                $this->ownerScans($request)->where('id', $id)->increment('photo_count', 1, ['updated_at' => now()]);
                return $this->details($this->owned($request, $id));
            });
        } catch (Throwable $e) {
            if ($path) $this->deleteFiles([$path]);
            throw $e;
        }
        return $this->data($details, 201);
    }

    public function photo(Request $request, int $id, int $photoId)
    {
        $this->owned($request, $id);
        $photo = $this->findPhoto($id, $photoId);
        try {
            $stream = Storage::disk('local')->readStream($photo->path);
        } catch (\League\Flysystem\FileNotFoundException $error) {
            abort(404, 'عکس پیدا نشد.');
        }
        abort_unless(is_resource($stream), 404, 'عکس پیدا نشد.');
        return response()->stream(function () use ($stream) {
            try { fpassthru($stream); } finally { fclose($stream); }
        }, 200, self::NO_CACHE + ['Content-Type' => $photo->mime_type, 'Content-Length' => $photo->size_bytes]);
    }

    public function destroyPhoto(Request $request, int $id, int $photoId)
    {
        [$scan, $path] = DB::transaction(function () use ($request, $id, $photoId) {
            $this->owned($request, $id, true);
            $photo = $this->findPhoto($id, $photoId);
            DB::table('spad_scan_photos')->where('scan_id', $id)->where('id', $photoId)->delete();
            $this->ownerScans($request)->where('id', $id)->decrement('photo_count', 1, ['updated_at' => now()]);
            return [$this->details($this->owned($request, $id)), $photo->path];
        });
        $this->deleteFiles([$path]);
        return $this->data($scan);
    }

    public function startRenovation(Request $request, int $id)
    {
        $this->owned($request, $id);
        $input = $request->validate(['copy_photos' => 'required|boolean']);
        abort_unless(Schema::hasTable('spad_renovation_projects') && Schema::hasTable('spad_renovation_media'), 503, 'بازسازی آنلاین در حال حاضر آماده نیست.');
        $copied = [];
        try {
            $project = DB::transaction(function () use ($request, $id, $input, &$copied) {
                $scan = $this->owned($request, $id, true);
                if ($scan->renovation_project_id && DB::table('spad_renovation_projects')->where('id', $scan->renovation_project_id)
                    ->where('user_id', $scan->user_id)->exists()) {
                    return ['project_id' => (int) $scan->renovation_project_id, 'created' => false];
                }
                [, $metrics] = SpadScanGeometry::prepare(json_decode($scan->rooms, true, 512, JSON_THROW_ON_ERROR));
                $area = (int) round(array_sum(array_column($metrics, 'floor_area_m2')));
                if ($area < 10 || $area > 2000 || mb_strlen($scan->title) < 3) {
                    throw ValidationException::withMessages(['area' => 'بازسازی آنلاین به عنوان حداقل ۳ حرف و مساحت کل بین ۱۰ تا ۲۰۰۰ متر مربع نیاز دارد.']);
                }
                $now = now();
                $projectId = DB::table('spad_renovation_projects')->insertGetId([
                    'user_id' => $scan->user_id, 'advertisement_id' => null, 'tour_id' => null, 'source_type' => 'user_upload',
                    'title' => $scan->title, 'area' => $area, 'status' => 'draft', 'design_style' => 'modern',
                    'plan' => '[]', 'created_at' => $now, 'updated_at' => $now,
                ]);
                if ($input['copy_photos']) {
                    $disk = Storage::disk('local');
                    foreach (DB::table('spad_scan_photos')->where('scan_id', $id)->orderBy('id')->get() as $photo) {
                        $path = 'private/renovation/' . $projectId . '/' . Str::uuid() . '.' . self::MIME[$photo->mime_type];
                        $copied[] = $path;
                        abort_unless($disk->copy($photo->path, $path), 503, 'کپی عکس‌ها کامل نشد. دوباره تلاش کنید.');
                        DB::table('spad_renovation_media')->insert([
                            'project_id' => $projectId, 'user_id' => $scan->user_id, 'kind' => 'source',
                            'room' => 'عکس مرجع نقشه', 'path' => $path, 'mime' => $photo->mime_type,
                            'bytes' => $photo->size_bytes, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
                $this->ownerScans($request)->where('id', $id)->update(['renovation_project_id' => $projectId, 'updated_at' => $now]);
                return ['project_id' => $projectId, 'created' => true];
            });
        } catch (Throwable $e) {
            if ($copied) $this->deleteFiles($copied);
            throw $e;
        }
        return $this->data($project, $project['created'] ? 201 : 200);
    }

    public function materials(Request $request)
    {
        $this->userId($request);
        return $this->data($this->catalog()->map(static function ($row) {
            return [
                'id' => $row->id, 'name' => $row->name, 'unit' => $row->unit,
                'price_toman' => $row->price_toman === null ? null : (int) $row->price_toman,
                'source_updated_at' => $row->source_updated_at, 'source_type' => $row->source_type,
            ];
        })->values());
    }

    public function estimate(Request $request)
    {
        $this->userId($request);
        $values = $this->geometryInput($request, false);
        $waste = (float) ($values['wastage_percent'] ?? 0);
        $surfaces = ['floor', 'wall', 'ceiling'];
        $materialIds = $values['material_ids'] ?? [];
        $catalog = $materialIds ? $this->catalog(array_values(array_filter($materialIds)))->keyBy('id') : collect();
        $metrics = $values['metrics'];
        $response = [
            'generated_at' => now()->toIso8601String(), 'wastage_percent' => $waste,
            'rooms' => $this->roundMetrics($metrics), 'items' => [],
            'priced_subtotal_toman' => null, 'priced_subtotal_max_toman' => null,
        ];
        foreach ($surfaces as $surface) {
            $key = $surface . '_area_m2';
            $area = array_sum(array_column($metrics, $key));
            $response[$key] = round($area, 4);
            $item = $catalog->get($materialIds[$surface] ?? 0);
            $qty = round($area * (1 + $waste / 100), 4);
            $price = $item->price_toman ?? null;
            $maximum = $item->price_max_toman ?? $price;
            $subtotal = $price !== null ? (int) round($qty * $price) : null;
            $maxSubtotal = $maximum !== null ? (int) round($qty * $maximum) : null;
            $response['items'][] = [
                'surface' => $surface, 'area_m2' => round($area, 4), 'quantity_m2' => $qty,
                'material_id' => $item->id ?? null, 'material_name' => $item->name ?? null,
                'price_toman' => $price === null ? null : (int) $price,
                'price_max_toman' => $maximum === null ? null : (int) $maximum,
                'source_updated_at' => $item->source_updated_at ?? null,
                'source_type' => $item->source_type ?? null, 'source_url' => $item->source_url ?? null,
                'subtotal_toman' => $subtotal, 'subtotal_max_toman' => $maxSubtotal,
            ];
            if ($subtotal !== null) {
                $response['priced_subtotal_toman'] = ($response['priced_subtotal_toman'] ?? 0) + $subtotal;
                $response['priced_subtotal_max_toman'] = ($response['priced_subtotal_max_toman'] ?? 0) + $maxSubtotal;
            }
        }
        return $this->data($response);
    }

    private function geometryInput(Request $request, bool $needsTitle): array
    {
        $geometry = $request->validate([
            'title' => $needsTitle ? 'required|string|max:120' : 'sometimes|string|max:120',
            'wastage_percent' => 'sometimes|numeric|min:0|max:35',
            'material_ids' => 'sometimes|array:floor,wall,ceiling',
            'material_ids.*' => 'nullable|integer|min:1',
            'rooms' => 'required|array|min:1|max:20',
            'rooms.*' => 'array:name,height,points,openings,objects',
            'rooms.*.name' => 'required|string|max:80',
            'rooms.*.height' => 'required|numeric|min:0.5|max:12',
            'rooms.*.points' => 'required|array|min:3|max:20',
            'rooms.*.points.*' => 'array:x,y',
            'rooms.*.points.*.x' => 'required|numeric|between:-2000,2000',
            'rooms.*.points.*.y' => 'required|numeric|between:-2000,2000',
            'rooms.*.openings' => 'sometimes|array|max:40',
            'rooms.*.openings.*' => 'array:type,wall_index,offset,width,height,sill',
            'rooms.*.openings.*.type' => 'required|in:door,window,other',
            'rooms.*.openings.*.wall_index' => 'required|integer|min:0|max:19',
            'rooms.*.openings.*.offset' => 'required|numeric|min:0|max:100',
            'rooms.*.openings.*.width' => 'required|numeric|min:0.05|max:100',
            'rooms.*.openings.*.height' => 'required|numeric|min:0.05|max:12',
            'rooms.*.openings.*.sill' => 'required|numeric|min:0|max:12',
            'rooms.*.objects' => 'sometimes|array|max:40',
            'rooms.*.objects.*' => 'array:name,x,y,width,depth,height',
            'rooms.*.objects.*.name' => 'required|string|max:80',
            'rooms.*.objects.*.x' => 'required|numeric|between:-2000,2000',
            'rooms.*.objects.*.y' => 'required|numeric|between:-2000,2000',
            'rooms.*.objects.*.width' => 'required|numeric|min:0.05|max:100',
            'rooms.*.objects.*.depth' => 'required|numeric|min:0.05|max:100',
            'rooms.*.objects.*.height' => 'required|numeric|min:0.05|max:12',
        ], ['required' => 'همهٔ اندازه‌ها و عنوان‌های لازم را وارد کنید.', 'numeric' => 'اندازه‌ها باید به‌صورت عدد وارد شوند.']);

        try {
            [$geometry['rooms'], $geometry['metrics']] = SpadScanGeometry::prepare($geometry['rooms']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['rooms' => $e->getMessage()]);
        }
        return $geometry;
    }

    private function ownerScans(Request $request)
    {
        return DB::table('spad_scans')->where('user_id', $this->userId($request));
    }

    private function userId(Request $request): int
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403, 'برای دسترسی به نقشه با حساب کاربری مشتری وارد شوید.');
        return (int) $user->getAuthIdentifier();
    }

    private function owned(Request $request, int $id, bool $lock = false)
    {
        $query = $this->ownerScans($request)->where('id', $id);
        $scan = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($scan, 404, 'نقشه پیدا نشد.');
        return $scan;
    }

    private function row(array $values): array
    {
        return [
            'title' => trim($values['title']), 'rooms' => json_encode($values['rooms'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'material_ids' => empty($values['material_ids']) ? null : json_encode($values['material_ids'], JSON_THROW_ON_ERROR),
            'wastage_percent' => $values['wastage_percent'] ?? 0,
            'room_count' => count($values['rooms']), 'updated_at' => now(),
        ];
    }

    private function details(object $row): array
    {
        return [
            'id' => $row->id, 'title' => $row->title, 'rooms' => json_decode($row->rooms, true, 512, JSON_THROW_ON_ERROR),
            'material_ids' => $row->material_ids ? json_decode($row->material_ids, false, 512, JSON_THROW_ON_ERROR) : (object) [],
            'wastage_percent' => (float) $row->wastage_percent,
            'renovation_project_id' => $row->renovation_project_id ? (int) $row->renovation_project_id : null,
            'photos' => DB::table('spad_scan_photos')->where('scan_id', $row->id)->orderBy('id')->get(['id', 'name']),
            'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
        ];
    }

    private function findPhoto(int $scanId, int $photoId)
    {
        $photo = DB::table('spad_scan_photos')->where('scan_id', $scanId)->where('id', $photoId)->first();
        abort_unless($photo, 404, 'عکس پیدا نشد.');
        return $photo;
    }

    private function deleteFiles(array $paths): void
    {
        try {
            if (!Storage::disk('local')->delete($paths)) {
                report(new \RuntimeException('SPAD Scan private photo deletion could not complete.'));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function catalog(array $ids = []): Collection
    {
        if (!Schema::hasTable('material_price_settings') || !Schema::hasTable('material_price_products')
            || !DB::table('material_price_settings')->where('id', 1)->value('enabled')) {
            return collect();
        }
        $rows = DB::table('material_price_products')->where('enabled', true)->orderBy('sort_order')->orderBy('id');
        if ($ids) $rows->whereIn('id', $ids);
        return $rows->limit(1500)->get(['id', 'name', 'unit', 'price_toman', 'price_max_toman', 'source_updated_at', 'source_type', 'source_url'])
            ->filter(static function ($row) {
                $unit = mb_strtolower(preg_replace('/[\s‌]+/u', '', $row->unit));
                return in_array($unit, ['مترمربع', 'مترمربّع', 'm²', 'm2', 'مترمربع(m2)'], true);
            });
    }

    private function roundMetrics(array $rooms): array
    {
        foreach ($rooms as &$room) {
            foreach (['perimeter_m', 'floor_area_m2', 'ceiling_area_m2', 'wall_area_m2', 'openings_area_m2'] as $key) {
                $room[$key] = round($room[$key], 4);
            }
            $room['wall_lengths_m'] = array_map(static function ($length) { return round($length, 4); }, $room['wall_lengths_m']);
        }
        return $rooms;
    }

    private function data($data, int $status = 200)
    {
        return response()->json(['data' => $data], $status, self::NO_CACHE);
    }
}
