<?php

namespace Tests\Feature;

use App\Http\Middleware\SpadHomeService;
use App\Models\User;
use App\Services\SpadSmartHome\GatewayRegistry;
use App\Services\SpadSmartHome\HomeAssistant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadSmartHomeTest extends TestCase
{
    private const BASE = '/api/smart-home';
    private const CREDENTIAL = 'fixture.home-assistant.dedicated-token';

    private $provider;
    private $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for isolated storage.');
        }
        $this->configuration = [
            'studio' => [
                'owner_id' => 301, 'label' => 'چراغ‌های خانه', 'host' => 'house.example.com', 'ipv4' => '93.184.216.34',
                'lights' => [
                    'light.living_room' => ['label' => 'چراغ نشیمن', 'control' => true],
                    'light.hall' => ['label' => 'چراغ راهرو', 'control' => false],
                ],
            ],
        ];
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('x', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'spad_smart_home.gateways' => $this->configuration,
        ]);
        DB::purge('sqlite');
        require_once database_path('migrations/2026_11_21_183000_create_spad_smart_home_tables.php');
        (new \CreateSpadSmartHomeTables())->up();
        $this->withoutMiddleware(SpadHomeService::class);
        Carbon::setTestNow(Carbon::parse('2026-11-21 12:00:00', 'UTC'));
        $this->provider = new class extends HomeAssistant {
            public $state = 'off';
            public $commands = [];
            public $movementReported = true;
            public $challengeCount = 0;

            protected function call(array $gateway, string $credential, string $path, ?array $body = null): array
            {
                if ($credential !== 'fixture.home-assistant.dedicated-token') {
                    return ['status' => 401, 'data' => []];
                }
                if ($path === '/api/') {
                    $this->challengeCount++;
                    return ['status' => 200, 'data' => ['message' => 'API running.']];
                }
                if (strpos($path, '/api/services/') === 0) {
                    $this->commands[] = [$path, $body];
                    if ($this->movementReported) {
                        $this->state = substr($path, strrpos($path, '_') + 1);
                    }
                    return ['status' => 200, 'data' => []];
                }
                return ['status' => 200, 'data' => [
                    'entity_id' => basename($path), 'state' => $this->state,
                    'attributes' => ['do_not_expose' => 'unrelated household metadata'],
                ]];
            }
        };
        $this->instance(HomeAssistant::class, $this->provider);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_owner_can_provision_and_api_excludes_host_credential_and_private_attributes(): void
    {
        $this->getJson(self::BASE)->assertUnauthorized();
        $this->asCustomer(302);
        $this->getJson(self::BASE)->assertOk()->assertJsonCount(0, 'data.gateways');
        $this->postJson(self::BASE . '/connection', ['gateway' => 'studio', 'credential' => self::CREDENTIAL])->assertStatus(422);
        $this->assertSame(0, $this->provider->challengeCount);

        $this->asCustomer(301);
        $version = $this->connect();
        $stored = DB::table('spad_smart_home_connections')->where('user_id', 301)->value('encrypted_credential');
        $this->assertNotSame(self::CREDENTIAL, $stored);
        $this->assertSame(self::CREDENTIAL, Crypt::decryptString($stored));
        $data = $this->getJson(self::BASE)->assertOk()->assertJsonPath('data.connection.version', $version)
            ->assertJsonPath('data.devices.0.state', 'off')->assertJsonCount(2, 'data.devices')->getContent();
        foreach (['house.example.com', '93.184.216.34', self::CREDENTIAL, 'unrelated household metadata'] as $private) {
            $this->assertStringNotContainsString($private, $data);
        }

        $this->asCustomer(302);
        $this->getJson(self::BASE)->assertJsonPath('data.connection', null)->assertJsonCount(0, 'data.devices');
        $this->getJson(self::BASE . '/audit')->assertJsonCount(0, 'data');
    }

    public function test_controls_enforce_allowlist_confirmation_and_idempotency_and_verify_actual_state(): void
    {
        $this->asCustomer(301);
        $version = $this->connect();
        $payload = [
            'request_id' => (string) Str::uuid(), 'connection_version' => $version, 'confirmed' => true,
            'entity_id' => 'light.living_room', 'state' => 'on',
        ];
        $actions = self::BASE . '/actions';
        $this->postJson($actions, ['confirmed' => false] + $payload)->assertStatus(422);
        $this->postJson($actions, ['entity_id' => 'light.hall'] + $payload)->assertForbidden();
        $this->postJson($actions, ['entity_id' => 'light.unassigned'] + $payload)->assertForbidden();
        $this->postJson($actions, ['entity_id' => 'lock.front_door'] + $payload)->assertStatus(422);
        $this->assertSame([], $this->provider->commands);

        $this->postJson($actions, $payload)->assertOk()->assertJsonPath('data.result', 'confirmed')->assertJsonPath('data.observed_state', 'on');
        $this->postJson($actions, $payload)->assertOk()->assertJsonPath('data.result', 'confirmed');
        $this->postJson($actions, ['state' => 'off'] + $payload)->assertStatus(409);
        $this->assertSame([['/api/services/light/turn_on', ['entity_id' => 'light.living_room']]], $this->provider->commands);

        $this->provider->movementReported = false;
        $this->postJson($actions, ['state' => 'off', 'request_id' => (string) Str::uuid()] + $payload)->assertOk()
            ->assertJsonPath('data.result', 'unconfirmed')->assertJsonPath('data.observed_state', 'on');
        $this->getJson(self::BASE . '/audit')->assertJsonCount(3, 'data')->assertJsonPath('data.0.result', 'unconfirmed');
    }

    public function test_expiry_and_changed_configuration_prevent_commands_and_revocation_erases_credential(): void
    {
        $this->asCustomer(301);
        $version = $this->connect();
        $payload = [
            'request_id' => (string) Str::uuid(), 'connection_version' => $version, 'confirmed' => true,
            'entity_id' => 'light.living_room', 'state' => 'on',
        ];
        $revised = $this->configuration;
        $revised['studio']['lights']['light.living_room']['control'] = false;
        config(['spad_smart_home.gateways' => $revised]);
        $this->getJson(self::BASE)->assertJsonPath('data.connection.status', 'reconnect_required')->assertJsonCount(0, 'data.devices');
        $this->postJson(self::BASE . '/actions', $payload)->assertStatus(409);
        config(['spad_smart_home.gateways' => $this->configuration]);
        Carbon::setTestNow(Carbon::parse('2026-12-22 12:00:00', 'UTC'));
        $this->postJson(self::BASE . '/actions', $payload)->assertStatus(409);
        $this->assertSame([], $this->provider->commands);

        $this->deleteJson(self::BASE . '/connection', ['connection_version' => (string) Str::uuid()])->assertStatus(409);
        $this->deleteJson(self::BASE . '/connection', ['connection_version' => $version])->assertOk();
        $this->assertSame(0, DB::table('spad_smart_home_connections')->count());
        $this->getJson(self::BASE . '/audit')->assertJsonPath('data.0.event', 'disconnected');
    }

    public function test_gateway_configuration_rejects_non_public_ips_invalid_hosts_and_unapproved_domains(): void
    {
        $gateway = $this->configuration['studio'];
        foreach (['127.0.0.1', '169.254.169.254', '10.2.3.4', '100.64.1.2', '224.0.0.1', '192.0.2.1'] as $ip) {
            config(['spad_smart_home.gateways.studio' => ['ipv4' => $ip] + $gateway]);
            $this->assertSame([], (new GatewayRegistry())->forOwner(301));
        }
        foreach (['https://house.example.com', 'house.example.com:8443', 'house.example.com@another.com', 'localhost'] as $host) {
            config(['spad_smart_home.gateways.studio' => ['host' => $host] + $gateway]);
            $this->assertSame([], (new GatewayRegistry())->forOwner(301));
        }
        config(['spad_smart_home.gateways.studio' => ['lights' => ['switch.pump' => ['label' => 'پمپ', 'control' => true]]] + $gateway]);
        $this->assertSame([], (new GatewayRegistry())->forOwner(301));
    }

    private function asCustomer(int $id): void
    {
        Sanctum::actingAs((new User())->forceFill(['id' => $id]));
    }

    private function connect(): string
    {
        $this->postJson(self::BASE . '/connection', ['gateway' => 'studio', 'credential' => self::CREDENTIAL])->assertCreated();
        return $this->getJson(self::BASE)->assertOk()->json('data.connection.version');
    }
}
