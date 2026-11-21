<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadHouseholdVision;
use App\Services\HomeEnergy\UtilityCapabilities;
use App\Services\SpadSmartHome\GatewayRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpadHomeServicesController extends Controller
{
    private const HOUSEHOLD = [
        'energy' => ['locations' => 'spad_home_energy_locations', 'accounts' => 'spad_home_energy_accounts', 'bills' => 'spad_home_energy_bills'],
        'book' => ['homes' => 'spad_homebook_homes', 'devices' => 'spad_homebook_devices', 'documents' => 'spad_homebook_documents'],
        'planner' => ['groups' => 'spad_home_planner_groups', 'tasks' => 'spad_home_planner_tasks', 'members' => 'spad_home_planner_members'],
        'sunlight' => ['projects' => 'spad_sunlight_projects'],
        'garden' => ['plants' => 'spad_garden_plants', 'photos' => 'spad_garden_photos', 'care_logs' => 'spad_garden_activity'],
        'smart' => ['connections' => 'spad_smart_home_connections', 'actions' => 'spad_smart_home_audit'],
    ];

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
            if (isset(self::HOUSEHOLD[$row->service])) {
                $result['household_inventory'] = [];
                foreach (self::HOUSEHOLD[$row->service] as $key => $table) {
                    if (Schema::hasTable($table)) {
                        $result['household_inventory'][$key] = DB::table($table)->count();
                    }
                }
            }
            if (in_array($row->service, ['garden', 'book', 'energy', 'smart'], true)) {
                try {
                    $result['provider_status'] = $this->providerStatus($row->service);
                } catch (\Throwable $error) {
                    $result['provider_status'] = ['state' => 'unknown'];
                }
            }
            return $result;
        });
        return response()->json(['data' => $settings])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $service)
    {
        abort_unless(isset(self::HOUSEHOLD[$service]) || in_array($service, ['scan', 'doctor', 'neighbors', 'league'], true), 404);
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

    private function providerStatus(string $service): array
    {
        if (in_array($service, ['garden', 'book'], true)) {
            $status = Schema::hasTable('spad_ai_assistant_settings')
                ? app(SpadHouseholdVision::class)->status()
                : ['state' => 'not_configured'];
            $state = $status['state'] ?? 'unknown';
            $states = ['not_configured', 'configured_unchecked', 'responding', 'quota_exhausted',
                'rate_limited', 'auth_error', 'unavailable', 'invalid_response'];
            $checked = $status['checked_at'] ?? null;
            return [
                'state' => in_array($state, $states, true) ? $state : 'unknown',
                'last_observed_at' => is_string($checked) && preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z\z/', $checked) ? $checked : null,
                'image_preparation_ready' => function_exists('imagecreatefromstring') && function_exists('imagejpeg'),
            ];
        }

        if ($service === 'energy') {
            $capabilities = app(UtilityCapabilities::class)->read();
            $available = array_intersect(['water', 'electricity', 'gas'], array_keys((array) $capabilities['inquiry_services']));
            return [
                'state' => !$capabilities['inquiry_configured'] ? 'not_configured' : ($available ? 'configuration_ready' : 'configuration_incomplete'),
                'inquiry_services' => array_values($available),
                'payment_enabled' => (bool) $capabilities['payment_enabled'],
                'ocr_enabled' => (bool) $capabilities['ocr_enabled'],
                'receipt_images_ready' => (bool) $capabilities['receipt_images_enabled'],
            ];
        }

        $configured = config('spad_smart_home.gateways', []);
        $configured = is_array($configured) ? $configured : [];
        $owners = [];
        foreach ($configured as $item) {
            if (is_array($item) && is_int($item['owner_id'] ?? null)) $owners[$item['owner_id']] = true;
        }
        $gatewayCount = 0;
        $lightCount = 0;
        $controlledCount = 0;
        $registry = app(GatewayRegistry::class);
        foreach (array_keys($owners) as $owner) {
            foreach ($registry->forOwner($owner) as $gateway) {
                $gatewayCount++;
                foreach ($gateway['lights'] as $light) {
                    $lightCount++;
                    if ($light['control']) $controlledCount++;
                }
            }
        }
        return [
            'state' => $gatewayCount ? 'configuration_ready' : ($configured ? 'configuration_incomplete' : 'not_configured'),
            'gateway_count' => $gatewayCount,
            'light_count' => $lightCount,
            'control_allowed_count' => $controlledCount,
        ];
    }
}
