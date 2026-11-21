<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadSmartHome\GatewayRegistry;
use App\Services\SpadSmartHome\HomeAssistant;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SpadSmartHomeController extends Controller
{
    private const CONNECTIONS = 'spad_smart_home_connections';
    private const AUDIT = 'spad_smart_home_audit';

    private $gateways;
    private $provider;

    public function __construct(GatewayRegistry $gateways, HomeAssistant $provider)
    {
        $this->gateways = $gateways;
        $this->provider = $provider;
    }

    public function show(Request $request)
    {
        $userId = $this->owner($request);
        $gateways = $this->gateways->forOwner($userId);
        $connection = $this->connection($userId);
        $gateway = $this->activeGateway($connection, $gateways);
        $credential = $gateway ? $this->credential($connection) : null;
        $devices = [];

        if ($credential) {
            foreach ($gateway['lights'] as $id => $light) {
                $observed = $this->provider->readLight($gateway, $credential, $id);
                $devices[] = [
                    'id' => $id, 'label' => $light['label'], 'can_control' => $light['control'],
                    'request_id' => (string) Str::uuid(),
                ] + $observed;
            }
            $latest = $this->connection($userId);
            if (!$latest || $latest->version !== $connection->version) {
                return $this->json(['message' => 'اتصال تغییر کرد. وضعیت را دوباره دریافت کنید.'], 409);
            }
        }

        return $this->json(['data' => [
            'gateways' => array_values(array_map(function ($item) { return ['id' => $item['id'], 'label' => $item['label']]; }, $gateways)),
            'connection' => $connection ? [
                'status' => $credential ? 'connected' : 'reconnect_required',
                'gateway' => $connection->gateway,
                'version' => $connection->version,
                'authenticated_at' => $this->iso($connection->authenticated_at),
                'expires_at' => $this->iso($connection->expires_at),
            ] : null,
            'devices' => $devices,
        ]]);
    }

    public function connect(Request $request)
    {
        $userId = $this->owner($request);
        $gatewayId = $request->input('gateway');
        $credential = $request->input('credential');
        $connectionVersion = $request->input('connection_version');
        $gateways = $this->gateways->forOwner($userId);
        $gateway = is_string($gatewayId) ? ($gateways[$gatewayId] ?? null) : null;
        $versionValidation = Validator::make(['version' => $connectionVersion], ['version' => 'nullable|uuid']);
        if (!$gateway || $versionValidation->fails()
            || !is_string($credential) || !preg_match('/\A[A-Za-z0-9._~-]{30,2048}\z/', $credential)) {
            return $this->json(['message' => 'اتصال اختصاص‌یافته و توکن دسترسی معتبر را وارد کنید.'], 422);
        }
        $expected = $connectionVersion === null ? null : strtolower($connectionVersion);
        if (($this->connection($userId)->version ?? null) !== $expected) {
            return $this->json(['message' => 'اتصال تغییر کرده است. وضعیت را دوباره دریافت کنید.'], 409);
        }
        if (!$this->provider->authenticate($gateway, $credential)) {
            return $this->json(['message' => 'Home Assistant اتصال را تأیید نکرد. دسترسی توکن و درگاه را بررسی کنید.'], 422);
        }

        $now = now()->utc();
        $version = (string) Str::uuid();
        $values = [
            'gateway' => $gatewayId, 'configuration' => $gateway['configuration'], 'version' => $version,
            'encrypted_credential' => Crypt::encryptString($credential), 'authenticated_at' => $now,
            'expires_at' => $now->copy()->addDays(30), 'updated_at' => $now,
        ];
        DB::transaction(function () use ($userId, $now, $values, $version, $expected) {
            if ($expected === null) {
                $inserted = DB::table(self::CONNECTIONS)->insertOrIgnore($values + ['user_id' => $userId, 'created_at' => $now]);
                abort_unless($inserted, 409, 'اتصال تغییر کرده است. وضعیت را دوباره دریافت کنید.');
            } else {
                $locked = $this->connection($userId, true);
                abort_unless(($locked->version ?? null) === $expected, 409, 'اتصال تغییر کرده است. وضعیت را دوباره دریافت کنید.');
                DB::table(self::CONNECTIONS)->where('user_id', $userId)->update($values);
            }
            $this->log($userId, 'connected', $version);
        }, 3);

        return $this->json(['message' => 'اتصال Home Assistant تأیید و ذخیره شد.'], 201);
    }

    public function disconnect(Request $request)
    {
        $userId = $this->owner($request);
        $data = $request->validate(['connection_version' => 'required|uuid']);
        DB::transaction(function () use ($userId, $data) {
            $connection = $this->connection($userId, true);
            if ($connection) {
                abort_unless($connection->version === strtolower($data['connection_version']), 409, 'اتصال تغییر کرده است. وضعیت را دوباره دریافت کنید.');
                DB::table(self::CONNECTIONS)->where('user_id', $userId)->delete();
                $this->log($userId, 'disconnected', $connection->version);
            }
        }, 3);
        return $this->json(['message' => 'اتصال از اسپاد حذف شد.']);
    }

    public function action(Request $request)
    {
        $userId = $this->owner($request);
        $data = $request->validate([
            'request_id' => 'required|uuid',
            'connection_version' => 'required|uuid',
            'entity_id' => ['required', 'string', 'regex:/\Alight\.[a-z0-9_]{1,80}\z/'],
            'state' => 'required|in:on,off',
            'confirmed' => 'accepted',
        ]);
        $data['request_id'] = strtolower($data['request_id']);
        $data['connection_version'] = strtolower($data['connection_version']);
        $gateways = $this->gateways->forOwner($userId);
        [$id, $prior] = DB::transaction(function () use ($userId, $data, $gateways) {
            $connection = $this->connection($userId, true);
            $existing = DB::table(self::AUDIT)->where('user_id', $userId)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->event === 'light_command' && $existing->connection_version === $data['connection_version']
                    && $existing->entity_id === $data['entity_id'] && $existing->desired_state === $data['state'], 409, 'این شناسه برای درخواست دیگری استفاده شده است.');
                return [$existing->id, $existing];
            }
            $gateway = $this->activeGateway($connection, $gateways);
            $light = $gateway['lights'][$data['entity_id']] ?? null;
            abort_unless($gateway && $connection->version === $data['connection_version'], 409, 'اتصال تغییر کرده است. وضعیت را دوباره دریافت کنید.');
            abort_unless($light && $light['control'], 403, 'فرمان برای این چراغ مجاز نیست.');
            $now = now()->utc();
            $id = DB::table(self::AUDIT)->insertGetId([
                'user_id' => $userId, 'request_id' => $data['request_id'], 'connection_version' => $connection->version,
                'event' => 'light_command', 'entity_id' => $data['entity_id'], 'device_label' => $light['label'],
                'desired_state' => $data['state'], 'result' => 'unconfirmed', 'created_at' => $now, 'updated_at' => $now,
            ]);
            return [$id, null];
        }, 3);

        // The intent is durable before dispatch. A lost result can never cause a replay of the upstream request.
        $result = $prior ?: DB::transaction(function () use ($id, $userId, $data, $gateways) {
            $connection = $this->connection($userId, true);
            $gateway = $this->activeGateway($connection, $gateways);
            $observed = null;
            $outcome = 'canceled';
            if ($gateway && $connection->version === $data['connection_version'] && !empty($gateway['lights'][$data['entity_id']]['control'])) {
                $credential = $this->credential($connection);
                $outcome = 'unavailable';
                if ($credential) {
                    [$outcome, $observed] = $this->executeLight($gateway, $credential, $data['entity_id'], $data['state']);
                }
            }
            $now = now()->utc();
            DB::table(self::AUDIT)->where('id', $id)->update([
                'result' => $outcome, 'observed_state' => $observed, 'finished_at' => $now, 'updated_at' => $now,
            ]);
            return DB::table(self::AUDIT)->where('id', $id)->first();
        });

        return $this->json(['data' => $this->auditData($result)]);
    }

    public function audit(Request $request)
    {
        $userId = $this->owner($request);
        $page = DB::table(self::AUDIT)->where('user_id', $userId)->orderByDesc('id')->simplePaginate(20);
        return $this->json([
            'data' => $page->getCollection()->map(function ($row) { return $this->auditData($row); })->values(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    private function executeLight(array $gateway, string $credential, string $entity, string $desired): array
    {
        $current = $this->provider->readLight($gateway, $credential, $entity)['state'];
        if (!in_array($current, ['on', 'off'], true)) {
            return ['unavailable', $current];
        }
        if ($current !== $desired) {
            $this->provider->commandLight($gateway, $credential, $entity, $desired);
            $current = $this->provider->readLight($gateway, $credential, $entity)['state'];
        }
        return [$current === $desired ? 'confirmed' : 'unconfirmed', $current];
    }

    private function activeGateway($connection, array $gateways): ?array
    {
        $gateway = $connection ? ($gateways[$connection->gateway] ?? null) : null;
        if (!$gateway || !hash_equals($gateway['configuration'], $connection->configuration)
            || Carbon::parse($connection->expires_at, 'UTC')->lte(now()->utc())) {
            return null;
        }
        return $gateway;
    }

    private function credential($connection): ?string
    {
        try {
            return Crypt::decryptString($connection->encrypted_credential);
        } catch (DecryptException $error) {
            return null;
        }
    }

    private function connection(int $userId, bool $lock = false)
    {
        $query = DB::table(self::CONNECTIONS)->where('user_id', $userId);
        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function log(int $userId, string $event, string $version): void
    {
        $now = now()->utc();
        DB::table(self::AUDIT)->insert([
            'user_id' => $userId, 'request_id' => (string) Str::uuid(), 'connection_version' => $version,
            'event' => $event, 'result' => 'confirmed', 'finished_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function auditData($row): array
    {
        return [
            'request_id' => $row->request_id, 'event' => $row->event, 'device_label' => $row->device_label,
            'desired_state' => $row->desired_state, 'observed_state' => $row->observed_state, 'result' => $row->result,
            'created_at' => $this->iso($row->created_at), 'finished_at' => $row->finished_at ? $this->iso($row->finished_at) : null,
        ];
    }

    private function iso(string $date): string
    {
        return Carbon::parse($date, 'UTC')->toIso8601String();
    }

    private function owner(Request $request): int
    {
        $user = $request->user();
        abort_unless($user instanceof User && (int) $user->id > 0 && ($user->currentAccessToken()->name ?? null) !== 'partner-token',
            403, 'با حساب عمومی اسپاد وارد شوید.');
        abort_unless(Schema::hasTable(self::CONNECTIONS) && Schema::hasTable(self::AUDIT), 503, 'خانهٔ هوشمند در حال راه‌اندازی است.');
        return (int) $user->id;
    }

    private function json(array $payload, int $status = 200)
    {
        return response()->json($payload, $status)->header('Cache-Control', 'private, no-store');
    }
}
