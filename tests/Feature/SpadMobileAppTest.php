<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SpadAppDevice;
use App\Models\SpadAppNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * App settings and notifications. Runs on an in-memory SQLite database with
 * only the tables these endpoints touch, so it needs no copy of production.
 */
class SpadMobileAppTest extends TestCase
{
    private const INSTALL_ID = 'test-install-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('mobile')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->integer('status')->default(1);
            $t->timestamp('phone_verified_at')->nullable();
            $t->rememberToken();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('username')->nullable();
            $t->string('password')->nullable();
            $t->integer('status')->default(1);
            $t->unsignedBigInteger('shop_id')->nullable();
            $t->rememberToken();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
        });

        (require base_path('database/migrations/2026_11_13_000000_create_spad_mobile_app.php'));
        (new \CreateSpadMobileApp())->up();
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::query()->forceCreate(['first_name' => 'مدیر', 'last_name' => 'اصلی', 'username' => 'root']);
        $roleId = DB::table('roles')->insertGetId(['name' => 'Super Admin', 'guard_name' => 'admin']);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => Admin::class, 'model_id' => $admin->id]);
        return $admin;
    }

    private function operator(): Admin
    {
        return Admin::query()->forceCreate(['first_name' => 'اپراتور', 'last_name' => 'ساده', 'username' => 'op']);
    }

    private function user(string $mobile = '09120000001'): User
    {
        return User::query()->forceCreate(['first_name' => 'کاربر', 'last_name' => 'اپ', 'mobile' => $mobile]);
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'maintenance_enabled' => false,
            'maintenance_message' => 'در حال به‌روزرسانی',
            'latest_version' => '1.1.0',
            'min_version' => '1.0.0',
            'update_url' => 'https://spad.estate/app',
            'update_message' => 'نسخه تازه',
            'announcement_enabled' => true,
            'announcement_text' => 'تخفیف ویژه کارشناسی',
            'announcement_link' => '/services',
            'show_market_ticker' => false,
            'show_intro' => true,
            'support_phone' => '071-12345678',
            'notifications_enabled' => true,
            'notification_check_minutes' => 30,
        ], $overrides);
    }

    public function test_guest_reads_default_config()
    {
        $this->getJson('/api/app/config')
            ->assertOk()
            ->assertJsonPath('data.maintenance.enabled', false)
            ->assertJsonPath('data.features.market_ticker', true)
            ->assertJsonPath('data.notifications.enabled', true)
            ->assertJsonPath('data.notifications.check_minutes', 30);
    }

    public function test_super_admin_saves_settings_and_app_sees_them()
    {
        $this->actingAs($this->superAdmin(), 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload())
            ->assertOk()
            ->assertJsonPath('data.show_market_ticker', false)
            ->assertJsonPath('data.latest_version', '1.1.0');

        $this->getJson('/api/app/config')
            ->assertOk()
            ->assertJsonPath('data.features.market_ticker', false)
            ->assertJsonPath('data.update.min_version', '1.0.0')
            ->assertJsonPath('data.announcement.enabled', true)
            ->assertJsonPath('data.announcement.text', 'تخفیف ویژه کارشناسی')
            ->assertJsonPath('data.support_phone', '071-12345678');
    }

    public function test_settings_reject_inconsistent_versions_and_missing_text()
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload(['min_version' => '2.0.0']))
            ->assertStatus(422);
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload(['update_url' => '']))
            ->assertStatus(422);
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload(['announcement_text' => '']))
            ->assertStatus(422);
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload(['announcement_link' => 'javascript:alert(1)']))
            ->assertStatus(422);
    }

    public function test_only_super_admin_manages_the_app()
    {
        $operator = $this->operator();
        $this->actingAs($operator, 'admin')->getJson('/api/admin/mobile-app/settings')->assertStatus(403);
        $this->actingAs($operator, 'admin')
            ->postJson('/api/admin/mobile-app/notifications', ['title' => 'x', 'body' => 'y', 'audience' => 'all'])
            ->assertStatus(403);
        $this->getJson('/api/admin/mobile-app/settings')->assertStatus(401);
    }

    public function test_device_registration_links_signed_in_user()
    {
        $this->postJson('/api/app/devices', ['install_id' => self::INSTALL_ID, 'platform' => 'android', 'app_version' => '1.0.0'])
            ->assertOk();
        $this->assertNull(SpadAppDevice::query()->first()->user_id);

        $user = $this->user();
        $token = $user->createToken('app')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/app/devices', ['install_id' => self::INSTALL_ID, 'platform' => 'android', 'notifications_enabled' => false])
            ->assertOk();

        $device = SpadAppDevice::query()->first();
        $this->assertSame(1, SpadAppDevice::query()->count());
        $this->assertSame($user->id, (int) $device->user_id);
        $this->assertFalse($device->notifications_enabled);

        $this->postJson('/api/app/devices', ['install_id' => 'short', 'platform' => 'android'])->assertStatus(422);
        $this->postJson('/api/app/devices', ['install_id' => self::INSTALL_ID, 'platform' => 'windows'])->assertStatus(422);
    }

    public function test_notifications_reach_everyone_and_personal_ones_only_their_user()
    {
        $admin = $this->superAdmin();
        $alice = $this->user('09120000001');
        $bob = $this->user('09120000002');

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/notifications', ['title' => 'همگانی', 'body' => 'برای همه', 'audience' => 'all', 'link' => '/search'])
            ->assertStatus(201);
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/notifications', ['title' => 'شخصی', 'body' => 'فقط آلیس', 'audience' => 'user', 'mobile' => '09120000001'])
            ->assertStatus(201)
            ->assertJsonPath('data.user.mobile', '09120000001');
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/notifications', ['title' => 'x', 'body' => 'y', 'audience' => 'user', 'mobile' => '09129999999'])
            ->assertStatus(422);
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/app/notifications')->assertOk()->assertJsonCount(1, 'data');

        $aliceToken = $alice->createToken('app')->plainTextToken;
        $inbox = $this->withHeader('Authorization', 'Bearer ' . $aliceToken)->getJson('/api/app/notifications')
            ->assertOk()->assertJsonCount(2, 'data')->json();
        $this->assertSame('شخصی', $inbox['data'][0]['title']);
        $this->assertTrue($inbox['data'][0]['personal']);

        $this->app['auth']->forgetGuards();
        $bobToken = $bob->createToken('app')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $bobToken)->getJson('/api/app/notifications')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/app/notifications?after_id=' . $inbox['latest_id'])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_lists_and_deletes_notifications_and_switch_turns_inbox_off()
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/notifications', ['title' => 'عنوان', 'body' => 'متن', 'audience' => 'all'])
            ->assertStatus(201);
        $id = SpadAppNotification::query()->value('id');

        $this->actingAs($admin, 'admin')->getJson('/api/admin/mobile-app/notifications')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.admin', 'مدیر اصلی');

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/mobile-app/settings', $this->settingsPayload(['notifications_enabled' => false]))
            ->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/app/notifications')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin, 'admin')->deleteJson('/api/admin/mobile-app/notifications/' . $id)->assertOk();
        $this->assertSame(0, SpadAppNotification::query()->count());
    }
}
