<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpadHomeServicesController extends Controller
{
    public function index()
    {
        $iran = now('Asia/Tehran');
        $today = $iran->copy()->startOfDay()->utc();
        $month = $iran->copy()->subDays(29)->startOfDay()->utc();
        $events = DB::table('spad_home_service_events');
        $monthly = (clone $events)->where('created_at', '>=', $month)
            ->selectRaw('service, COUNT(*) as requests, COUNT(DISTINCT actor_hash) as users, AVG(duration_ms) as average_ms')
            ->groupBy('service')->get()->keyBy('service');
        $daily = (clone $events)->where('created_at', '>=', $today)
            ->selectRaw('service, COUNT(DISTINCT actor_hash) as active')->groupBy('service')->pluck('active', 'service');
        $errors = (clone $events)->where('created_at', '>=', $month)->where('http_status', '>=', 500)
            ->selectRaw('service, COUNT(*) as errors')->groupBy('service')->pluck('errors', 'service');
        $counts = (clone $events)->where('created_at', '>=', $month)->where('http_status', '<', 400)
            ->selectRaw('service, event, COUNT(*) as amount')->groupBy('service', 'event')->get()->groupBy('service');

        $settings = DB::table('spad_home_service_settings')->orderBy('service')->get()->map(function ($row) use ($monthly, $daily, $errors, $counts) {
            $stats = $monthly->get($row->service);
            $result = [
                'service' => $row->service,
                'enabled' => (bool) $row->enabled,
                'writes_enabled' => (bool) $row->writes_enabled,
                'writes_per_minute' => (int) $row->writes_per_minute,
                'guide' => $row->guide,
                'requests' => (int) optional($stats)->requests,
                'active_today' => (int) $daily->get($row->service, 0),
                'active_last_30_days' => (int) optional($stats)->users,
                'average_ms' => $stats ? (int) round($stats->average_ms) : null,
                'errors_last_30_days' => (int) $errors->get($row->service, 0),
                'completed_actions' => $counts->get($row->service, collect())->pluck('amount', 'event'),
            ];

            if ($row->service === 'scan') {
                $result['scan_summary'] = $this->scanSummary();
            }
            return $result;
        });
        return response()->json(['data' => $settings])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $service)
    {
        abort_unless(in_array($service, ['scan', 'doctor', 'neighbors', 'league'], true), 404);
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'writes_enabled' => 'required|boolean',
            'writes_per_minute' => 'required|integer|min:1|max:120',
            'guide' => 'nullable|string|max:600',
        ]);

        DB::transaction(function () use ($data, $service) {
            $before = DB::table('spad_home_service_settings')->where('service', $service)->lockForUpdate()->first();
            abort_unless($before, 404);
            DB::table('spad_home_service_settings')->where('service', $service)->update($data + ['updated_at' => now()]);
            DB::table('spad_home_service_admin_audit')->insert([
                'service' => $service, 'admin_id' => auth('admin')->id(),
                'changes' => json_encode(['before' => $before, 'after' => $data], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });
        return $this->index();
    }

    private function scanSummary(): ?array
    {
        if (!Schema::hasTable('spad_scans') || !Schema::hasTable('spad_scan_photos')) {
            return null;
        }

        $scans = DB::table('spad_scans')->selectRaw(
            'COUNT(*) AS projects, COALESCE(SUM(room_count), 0) AS rooms, COUNT(renovation_project_id) AS linked_renovations'
        )->first();
        $photos = DB::table('spad_scan_photos')->selectRaw(
            'COUNT(*) AS photos, COALESCE(SUM(size_bytes), 0) AS photo_bytes'
        )->first();

        return [
            'projects' => (int) $scans->projects,
            'rooms' => (int) $scans->rooms,
            'linked_renovations' => (int) $scans->linked_renovations,
            'photos' => (int) $photos->photos,
            'photo_bytes' => (int) $photos->photo_bytes,
        ];
    }
}
