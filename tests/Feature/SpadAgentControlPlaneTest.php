<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SpadAgentControlController;
use Illuminate\Http\Client\Request as Outgoing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SpadAgentControlPlaneTest extends TestCase
{
    private function adminRequest(bool $privileged): Request
    {
        $request = Request::create('/settings/agent-pro');
        $request->setUserResolver(fn () => new class($privileged) {
            public bool $privileged;
            public function __construct(bool $privileged) { $this->privileged = $privileged; }
            public function roles() { return $this; }
            public function where($field, $value) { return $this; }
            public function exists(): bool { return $this->privileged; }
            public function getKey(): int { return 9; }
        });
        return $request;
    }

    public function test_only_primary_administrators_can_access_agent_settings(): void
    {
        Http::fake();
        try {
            app(SpadAgentControlController::class)->index($this->adminRequest(false));
            $this->fail('Access must be denied.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        Http::assertNothingSent();
    }

    public function test_unconfigured_connection_does_not_contact_the_agent(): void
    {
        Http::fake();
        config(['services.spad_agent.control_key' => null]);
        $result = app(SpadAgentControlController::class)->index($this->adminRequest(true));
        $this->assertSame(['data' => ['status' => 'requires_configuration']], $result->getData(true));
        Http::assertNothingSent();
    }

    public function test_bridge_signs_requests_and_drops_private_fields(): void
    {
        $key = str_repeat('test-only-', 4);
        config(['services.spad_agent.control_key' => $key]);
        Http::fake(['https://agent.spad.estate/api/v1/control/overview' => Http::response(['data' => [
            'offices_by_status' => ['active' => 3, 'private' => 20],
            'providers' => [['id' => 'sms', 'status' => 'blocked', 'extra' => 'private'], ['id' => 'unknown', 'status' => 'verified']],
            'contacts' => ['private'],
        ]])]);
        $result = app(SpadAgentControlController::class)->index($this->adminRequest(true))->getData(true);
        $this->assertSame(['data' => ['status' => 'connected', 'offices' => ['trial' => 0, 'active' => 3, 'suspended' => 0], 'providers' => ['sms' => 'blocked']]], $result);
        Http::assertSent(function (Outgoing $request) use ($key) {
            $time = $request->header('X-Agent-Timestamp')[0];
            $nonce = $request->header('X-Agent-Nonce')[0];
            $signed = implode("\n", ['GET', '/api/v1/control/overview', $time, $nonce, hash('sha256', '')]);
            return $request->url() === 'https://agent.spad.estate/api/v1/control/overview'
                && hash_equals(hash_hmac('sha256', $signed, $key), $request->header('X-Agent-Signature')[0]);
        });
    }
}
