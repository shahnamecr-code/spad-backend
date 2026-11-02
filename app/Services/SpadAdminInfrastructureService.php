<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only server infrastructure snapshot. No shell execution, cPanel tokens,
 * env secrets, per-user identifiers, hostnames or filesystem paths are exposed.
 *
 * Disk/RAM metrics are host filesystem metrics, NOT the cPanel account quota.
 * Active count is distinct authenticated Sanctum users seen in the past 15min,
 * not an estimate of concurrent anonymous site visitors.
 */
class SpadAdminInfrastructureService
{
    public static function snapshot()
    {
        $server = [
            'api_ok' => true, // Reaching this authenticated API confirms PHP is responding.
            'database_ok' => null,
            'disk' => null,
            'memory' => null,
            'cpu_load_1m' => null,
            'uptime_seconds' => null,
            'active_authenticated_users_15m' => null,
            'active_users_source' => 'unavailable',
            'disk_source' => 'host_filesystem_not_cpanel_quota',
            'memory_source' => 'host_memory_not_cpanel_limit',
            'checked_at' => now()->toIso8601String(),
        ];
        try {
            DB::select('SELECT 1');
            $server['database_ok'] = true;
        } catch (\Throwable $e) {
            $server['database_ok'] = false;
        }

        try {
            // base_path is fixed; the browser cannot choose a filesystem path.
            $root = base_path();
            $total = @disk_total_space($root);
            $free = @disk_free_space($root);
            if (is_numeric($total) && $total > 0 && is_numeric($free) && $free >= 0) {
                $server['disk'] = [
                    'total_bytes' => (float)$total,
                    'used_bytes' => max(0, (float)$total - (float)$free),
                    'free_bytes' => (float)$free,
                    'used_percent' => round(100 * ((float)$total - (float)$free) / (float)$total, 1),
                ];
            }
        } catch (\Throwable $e) { /* unavailable, never substitute guesses */ }

        try {
            // Linux host RAM availability, NOT PHP memory_limit or hosting plan quota.
            if (is_readable('/proc/meminfo')) {
                $raw = @file_get_contents('/proc/meminfo');
                $values = [];
                if (is_string($raw) && preg_match_all('/^(MemTotal|MemAvailable):\s*(\d+) kB/m', $raw, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $values[$match[1]] = (float)$match[2] * 1024;
                    }
                }
                if (!empty($values['MemTotal']) && isset($values['MemAvailable'])) {
                    $total = $values['MemTotal'];
                    $available = max(0, min($total, $values['MemAvailable']));
                    $server['memory'] = [
                        'total_bytes' => $total,
                        'used_bytes' => $total - $available,
                        'used_percent' => round(100 * ($total - $available) / $total, 1),
                    ];
                }
            }
        } catch (\Throwable $e) { }

        try {
            if (function_exists('sys_getloadavg')) {
                $loads = @sys_getloadavg();
                if (is_array($loads) && isset($loads[0]) && is_numeric($loads[0])) {
                    $server['cpu_load_1m'] = round((float)$loads[0], 2);
                }
            }
            if (is_readable('/proc/uptime')) {
                $uptime = @file_get_contents('/proc/uptime');
                if (is_string($uptime) && preg_match('/^(\d+(?:\.\d+)?)/', $uptime, $match)) {
                    $server['uptime_seconds'] = (int)floor((float)$match[1]);
                }
            }
        } catch (\Throwable $e) { }

        try {
            if (Schema::hasTable('personal_access_tokens') &&
                Schema::hasColumn('personal_access_tokens', 'last_used_at')) {
                // Only actual authenticated API activity is measured.
                $server['active_authenticated_users_15m'] = DB::table('personal_access_tokens')
                    ->where('last_used_at', '>=', now()->subMinutes(15))
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->distinct()
                    ->count('tokenable_id');
                $server['active_users_source'] = 'sanctum_authenticated_15m_only';
            }
        } catch (\Throwable $e) { }

        return $server;
    }
}
