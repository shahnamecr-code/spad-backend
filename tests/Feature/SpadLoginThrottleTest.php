<?php

namespace Tests\Feature;

use App\Services\SpadLoginRateLimits;
use App\Services\SpadSmsService;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpadLoginThrottleTest extends TestCase
{
    private const MOBILE = '09120000001';
    private const PASSWORD = 'fake-test-password';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile');
            $table->string('password');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('login_requests', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->unique();
            $table->string('token');
            $table->timestamps();
        });
        Schema::create('partner_panels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        DB::table('users')->insert([
            'first_name' => 'کاربر', 'last_name' => 'آزمایشی', 'mobile' => self::MOBILE,
            'password' => Hash::make(self::PASSWORD), 'phone_verified_at' => now(),
        ]);
    }

    public function test_login_routes_have_their_own_traffic_limit_and_validate_normally(): void
    {
        $paths = [
            'sendLoginCode', 'login', 'loginWithPass',
            'partner-auth/send-login-code', 'partner-auth/login', 'partner-auth/login-with-pass',
        ];
        $router = $this->app['router'];
        for ($i = 0; $i < 32; $i++) RateLimiter::hit(sha1('|127.0.0.1'), 3600);
        foreach ($paths as $path) {
            $uri = '/api/'.$path;
            $request = Request::create($uri, 'POST');
            $route = $router->getRoutes()->match($request);
            $request->setRouteResolver(static function () use ($route) { return $route; });
            $this->assertContains('throttle:spad-login-traffic', $route->gatherMiddleware());
            $api = RateLimiter::limiter('api');
            $this->assertInstanceOf(Unlimited::class, $api($request));
            $this->postJson($uri)->assertStatus(422);
        }
    }

    public function test_repeated_sms_retains_one_short_cooldown_and_password_still_works(): void
    {
        $sentCode = null;
        $this->mock(SpadSmsService::class, function (MockInterface $sms) use (&$sentCode) {
            $sms->shouldReceive('sendOtp')->once()->andReturnUsing(
                static function ($event, $mobile, $code) use (&$sentCode) {
                    self::assertSame('login_otp', $event);
                    self::assertSame(self::MOBILE, $mobile);
                    $sentCode = $code;
                }
            );
        });
        $this->postJson('/api/sendLoginCode', ['mobile' => self::MOBILE])
            ->assertOk()->assertJsonPath('status', true)->assertJsonPath('resend_after', 90);
        $repeat = $this->postJson('/api/sendLoginCode', ['mobile' => self::MOBILE])
            ->assertStatus(429)->assertJsonPath('code', 'otp_cooldown');
        $this->assertGreaterThan(0, $repeat->json('retry_after'));
        $this->assertLessThanOrEqual(90, $repeat->json('retry_after'));
        $this->postJson('/api/loginWithPass', ['mobile' => self::MOBILE, 'password' => self::PASSWORD])
            ->assertOk()->assertJsonPath('status', true)->assertJsonStructure(['token']);
        $this->postJson('/api/login', ['mobile' => self::MOBILE, 'code' => $sentCode])
            ->assertOk()->assertJsonPath('status', true)->assertJsonStructure(['token']);
        $this->assertDatabaseCount('login_requests', 0);
    }

    public function test_provider_error_is_not_a_sent_code_window(): void
    {
        $this->mock(SpadSmsService::class, function (MockInterface $sms) {
            $sms->shouldReceive('sendOtp')->once()->andThrow(new \RuntimeException('internal provider error'));
        });
        $this->postJson('/api/sendLoginCode', ['mobile' => self::MOBILE])
            ->assertStatus(503)->assertJsonPath('code', 'otp_provider_unavailable');
        $this->assertDatabaseCount('login_requests', 0);
    }

    public function test_protection_against_guesses_isolated_by_phone_and_method(): void
    {
        for ($i = 0; $i < 12; $i++) SpadLoginRateLimits::attempt('password', self::MOBILE);
        try {
            SpadLoginRateLimits::attempt('password', self::MOBILE);
            $this->fail('Repeated guesses should be limited');
        } catch (HttpResponseException $error) {
            $this->assertSame(429, $error->getResponse()->getStatusCode());
            $this->assertSame('password_throttled', $error->getResponse()->getData(true)['code']);
        }
        $this->assertIsString(SpadLoginRateLimits::attempt('otp_send', self::MOBILE, '127.0.0.1'));
        $this->assertIsString(SpadLoginRateLimits::attempt('password', '09120000002'));
    }
}
