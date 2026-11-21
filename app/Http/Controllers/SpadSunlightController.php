<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadSunlight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SpadSunlightController extends Controller
{
    private const NO_STORE = ['Cache-Control' => 'private, no-store'];
    private const MAX_PROJECTS = 50;

    public function example()
    {
        $input = SpadSunlight::example();
        return $this->data(['educational' => true, 'input' => $input, 'result' => SpadSunlight::analyze($input)]);
    }

    public function analyze(Request $request)
    {
        $input = $this->validatedInput($request->all());
        return $this->data($this->compute($input));
    }

    public function index(Request $request)
    {
        return $this->data($this->projects($request)->orderByDesc('updated_at')->orderByDesc('id')->limit(self::MAX_PROJECTS)
            ->get(['id', 'title', 'updated_at']));
    }

    public function show(Request $request, int $id)
    {
        return $this->data($this->detail($this->project($request, $id)));
    }

    public function store(Request $request)
    {
        $owner = $this->owner($request);
        $values = $this->projectInput($request);
        $row = DB::transaction(function () use ($owner, $request, $values) {
            $owner->newQuery()->whereKey($owner->getAuthIdentifier())->lockForUpdate()->firstOrFail();
            if ($this->projects($request)->count() >= self::MAX_PROJECTS) {
                throw ValidationException::withMessages(['title' => 'حداکثر ۵۰ پروژه می‌توانید ذخیره کنید.']);
            }
            $id = DB::table('spad_sunlight_projects')->insertGetId($values + ['user_id' => $owner->getAuthIdentifier(), 'created_at' => now()]);
            return $this->project($request, $id);
        });
        return $this->data($this->detail($row), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->project($request, $id);
        $values = $this->projectInput($request);
        $row = DB::transaction(function () use ($request, $id, $values) {
            $this->project($request, $id, true);
            $this->projects($request)->where('id', $id)->update($values);
            return $this->project($request, $id);
        });
        return $this->data($this->detail($row));
    }

    public function destroy(Request $request, int $id)
    {
        abort_unless($this->projects($request)->where('id', $id)->delete(), 404, 'پروژه پیدا نشد.');
        return response()->noContent(204, self::NO_STORE);
    }

    public function fromScan(Request $request, int $id)
    {
        $owner = $this->owner($request);
        abort_unless(Schema::hasTable('spad_scans'), 404, 'اسکن پیدا نشد.');
        $scan = DB::table('spad_scans')->where('user_id', $owner->getAuthIdentifier())->where('id', $id)->first(['rooms']);
        abort_unless($scan, 404, 'اسکن پیدا نشد.');
        $rooms = json_decode($scan->rooms, true, 512, JSON_THROW_ON_ERROR);
        $result = [];
        foreach ($rooms as $index => $room) {
            $rectangle = $this->rectangle($room);
            if (!$rectangle) continue;
            $walls = ['front', 'right', 'back', 'left'];
            $windows = [];
            foreach ($room['openings'] ?? [] as $opening) {
                if (($opening['type'] ?? null) !== 'window' || !isset($walls[$opening['wall_index']])) continue;
                $windows[] = ['wall' => $walls[$opening['wall_index']], 'offset' => $opening['offset'], 'width' => $opening['width'],
                    'sill' => $opening['sill'], 'height' => $opening['height']];
            }
            $result[] = ['index' => $index, 'name' => $room['name'], 'room' => $rectangle, 'windows' => array_slice($windows, 0, 24),
                'orientation_required' => true, 'location_required' => true, 'obstacles_required_if_present' => true];
        }
        return $this->data(['rooms' => $result, 'skipped_count' => count($rooms) - count($result)]);
    }

    private function projectInput(Request $request): array
    {
        $values = $request->validate(['title' => 'required|string|max:120', 'input' => 'required|array']);
        $input = $this->validatedInput($values['input']);
        $this->compute($input);
        return ['title' => trim($values['title']), 'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'updated_at' => now()];
    }

    private function validatedInput(array $data): array
    {
        $walls = 'in:front,right,back,left';
        return validator($data, [
            'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180',
            'date' => 'required|date_format:Y-m-d', 'time' => 'required|date_format:H:i',
            'room' => 'required|array:width,depth,height,orientation_deg',
            'room.width' => 'required|numeric|between:0.5,40', 'room.depth' => 'required|numeric|between:0.5,40',
            'room.height' => 'required|numeric|between:0.5,12', 'room.orientation_deg' => 'required|numeric|between:0,360',
            'windows' => 'sometimes|array|max:24', 'windows.*' => 'array:wall,offset,width,sill,height',
            'windows.*.wall' => 'required|' . $walls, 'windows.*.offset' => 'required|numeric|between:0,40',
            'windows.*.width' => 'required|numeric|between:0.05,40', 'windows.*.sill' => 'required|numeric|between:0,12',
            'windows.*.height' => 'required|numeric|between:0.05,12',
            'obstacles' => 'sometimes|array|max:24', 'obstacles.*' => 'array:wall,offset,width,distance,depth,height',
            'obstacles.*.wall' => 'required|' . $walls, 'obstacles.*.offset' => 'required|numeric|between:-500,500',
            'obstacles.*.width' => 'required|numeric|between:0.05,500', 'obstacles.*.distance' => 'required|numeric|between:0,500',
            'obstacles.*.depth' => 'required|numeric|between:0.05,500', 'obstacles.*.height' => 'required|numeric|between:0.05,500',
        ], ['required' => 'اندازه‌ها، موقعیت و زمان لازم را کامل وارد کنید.', 'numeric' => 'مقادیر را به شکل عدد وارد کنید.'])->validate();
    }

    private function compute(array $input): array
    {
        try {
            return SpadSunlight::analyze($input);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['input' => $error->getMessage()]);
        }
    }

    private function rectangle(array $room): ?array
    {
        $points = $room['points'] ?? [];
        $height = (float) ($room['height'] ?? 0);
        if (count($points) !== 4 || $height < .5 || $height > 12) return null;
        $sides = [];
        $lengths = [];
        for ($i = 0; $i < 4; $i++) {
            $here = $points[$i];
            $next = $points[($i + 1) % 4];
            $sides[$i] = [$next['x'] - $here['x'], $next['y'] - $here['y']];
            $lengths[$i] = hypot(...$sides[$i]);
            if ($lengths[$i] < .5 || $lengths[$i] > 40) return null;
        }
        for ($i = 0; $i < 4; $i++) {
            $j = ($i + 1) % 4;
            $dot = $sides[$i][0] * $sides[$j][0] + $sides[$i][1] * $sides[$j][1];
            if (abs($dot) > .001 * $lengths[$i] * $lengths[$j]) return null;
        }
        return ['width' => $lengths[0], 'depth' => $lengths[1], 'height' => $height];
    }

    private function projects(Request $request)
    {
        return DB::table('spad_sunlight_projects')->where('user_id', $this->owner($request)->getAuthIdentifier());
    }

    private function project(Request $request, int $id, bool $locked = false)
    {
        $query = $this->projects($request)->where('id', $id);
        $row = ($locked ? $query->lockForUpdate() : $query)->first();
        abort_unless($row, 404, 'پروژه پیدا نشد.');
        return $row;
    }

    private function owner(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403, 'برای مدیریت پروژه با حساب اسپاد وارد شوید.');
        return $user;
    }

    private function detail(object $row): array
    {
        return ['id' => $row->id, 'title' => $row->title, 'updated_at' => $row->updated_at,
            'input' => json_decode($row->input, true, 512, JSON_THROW_ON_ERROR)];
    }

    private function data($value, int $status = 200)
    {
        return response()->json(['data' => $value], $status, self::NO_STORE);
    }
}
