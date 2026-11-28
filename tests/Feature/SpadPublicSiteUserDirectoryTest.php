<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpadPublicSiteUserDirectoryTest extends TestCase
{
    private const DIRECTORY = '/api/admin/site-usage/directory';
    private const PAGE_VIEW = '/api/site-usage/page-view';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Carbon::setTestNow(Carbon::parse('2026-11-28 00:02:00', 'Asia/Tehran'));

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2026_11_28_120000_create_spad_public_site_visitors_table.php',
            '2026_11_28_180000_create_spad_public_site_page_views_table.php',
        ] as $migration) {
            require_once database_path('migrations/' . $migration);
        }
        (new \CreateUsersTable())->up();
        Schema::table('users', static function (Blueprint $table) { $table->softDeletes(); });
        (new \CreateSpadPublicSiteVisitorsTable())->up();
        (new \CreateSpadPublicSitePageViewsTable())->up();

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->unsignedBigInteger('writable_id')->nullable();
            $table->string('writable_type')->nullable();
            $table->timestamps();
        });
        Schema::create('advertisement_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id');
            $table->string('property_slug');
            $table->string('text_value')->nullable();
            $table->text('textarea_value')->nullable();
        });
        Schema::create('mortgage_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_directory_includes_old_and_new_accounts_and_requires_the_main_admin_role(): void
    {
        $new = $this->user('مسعود', 'احمدی', '09120000001', now());
        $old = $this->user('علی', 'رحیمی', '09120000002', now()->subDay());
        $old->delete();
        $this->postJson(self::DIRECTORY . '/search')->assertUnauthorized();
        $this->admin(false);
        $this->postJson(self::DIRECTORY . '/search')->assertForbidden();
        $this->getJson(self::DIRECTORY . '/' . $new->id)->assertForbidden();

        $this->admin(true);
        $report = $this->postJson(self::DIRECTORY . '/search')->assertOk()
            ->assertJsonPath('data.summary.accounts', 2)->assertJsonPath('data.summary.new_accounts', 1)
            ->assertJsonPath('data.users.0.id', $new->id)->assertJsonPath('data.users.1.status', 'archived');
        $this->assertStringContainsString('no-store', $report->headers->get('Cache-Control'));
        $this->assertArrayNotHasKey('password', $report->json('data.users.0'));
        $this->assertArrayNotHasKey('remember_token', $report->json('data.users.0'));

        $this->postJson(self::DIRECTORY . '/search', ['q' => 'مسعود احمدی'])->assertOk()->assertJsonPath('data.total', 1);
        $this->postJson(self::DIRECTORY . '/search', ['q' => '۰۹۱۲۰۰۰۰۰۰۲'])->assertOk()->assertJsonPath('data.users.0.id', $old->id);
        $this->postJson(self::DIRECTORY . '/search', ['q' => '%'])->assertOk()->assertJsonPath('data.total', 0);
        $this->postJson(self::DIRECTORY . '/search', ['registered' => 'today'])->assertOk()->assertJsonPath('data.total', 1);
        $this->postJson(self::DIRECTORY . '/search', ['status' => 'archived'])->assertOk()->assertJsonPath('data.users.0.id', $old->id);
        $this->getJson(self::DIRECTORY . '/999')->assertNotFound();
    }

    public function test_only_public_website_accounts_can_record_deduplicated_page_and_property_views(): void
    {
        $user = $this->user('نام', 'کاربر', '09120000001', now());
        $event = ['event_key' => Str::uuid()->toString(), 'page' => '/search'];
        $this->postJson(self::PAGE_VIEW, $event)->assertUnauthorized();
        $this->actingAs($user->withAccessToken(new \Laravel\Sanctum\PersonalAccessToken(['name' => 'partner-token'])), 'sanctum');
        $this->postJson(self::PAGE_VIEW, $event)->assertForbidden();

        $this->actingAs($user->withAccessToken(new \Laravel\Sanctum\PersonalAccessToken(['name' => 'token'])), 'sanctum');
        $this->postJson(self::PAGE_VIEW, $event + ['user_id' => 99])->assertUnprocessable();
        $this->postJson(self::PAGE_VIEW, array_replace($event, ['page' => '/search?private=value']))->assertUnprocessable();
        $this->postJson(self::PAGE_VIEW, array_replace($event, ['page' => '/partner-panel']))->assertUnprocessable();
        $this->postJson(self::PAGE_VIEW, $event)->assertNoContent();
        $this->postJson(self::PAGE_VIEW, $event)->assertNoContent();
        $property = DB::table('advertisements')->insertGetId(['status' => Advertisement::APPROVED]);
        $view = ['event_key' => Str::uuid()->toString(), 'page' => '/property/[id]', 'property_id' => $property];
        $this->postJson(self::PAGE_VIEW, $view)->assertNoContent();
        $this->postJson(self::PAGE_VIEW, array_replace($view, ['property_id' => $property + 1]))->assertNotFound();
        $this->assertDatabaseCount('spad_public_site_page_views', 2);
        $this->assertDatabaseHas('spad_public_site_page_views', ['user_id' => $user->id, 'property_id' => $property]);
    }

    public function test_overview_counts_today_and_recent_distinct_accounts_across_tehran_midnight(): void
    {
        $old = $this->user('کاربر', 'قدیمی', '09120000001', now()->subDays(2));
        $new = $this->user('کاربر', 'جدید', '09120000002', now()->subMinute());
        $earlier = $this->user('کاربر', 'دیروز', '09120000003', now()->subDay());
        $recent = $this->user('کاربر', 'اخیر', '09120000004', now()->subDay());
        foreach ([
            [$old, 1, null], [$old, 1, 71], [$new, 2, null], [$earlier, 8, null], [$recent, 3, null],
        ] as [$user, $minutes, $propertyId]) {
            DB::table('spad_public_site_page_views')->insert([
                'user_id' => $user->id, 'event_key' => Str::uuid()->toString(),
                'page' => $propertyId === null ? '/services' : '/property/[id]',
                'property_id' => $propertyId, 'created_at' => now()->subMinutes($minutes),
            ]);
        }

        $path = self::DIRECTORY . '/overview';
        $this->getJson($path)->assertUnauthorized();
        $this->admin(false);
        $this->getJson($path)->assertForbidden();
        $this->admin(true);
        $this->getJson($path)->assertOk()->assertJsonPath('data.presence_minutes', 5)
            ->assertJsonPath('data.summary.online', 3)
            ->assertJsonPath('data.summary.registrations', 1)
            ->assertJsonPath('data.summary.active_accounts', 2)
            ->assertJsonPath('data.summary.page_views', 2)
            ->assertJsonPath('data.summary.property_views', 1)
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('data.days.0.active_accounts', null)
            ->assertJsonPath('data.days.5.active_accounts', 2)
            ->assertJsonPath('data.days.6.day', '2026-11-28')
            ->assertJsonPath('data.days.6.registrations', 1);
    }

    public function test_overview_keeps_registrations_when_tracking_is_unavailable(): void
    {
        Schema::drop('spad_public_site_page_views');
        $this->user('کاربر', 'جدید', '09120000001', now()->subMinute());
        $this->admin(true);
        $this->getJson(self::DIRECTORY . '/overview')->assertOk()
            ->assertJsonPath('data.summary.registrations', 1)
            ->assertJsonPath('data.summary.online', null)
            ->assertJsonPath('data.summary.page_views', null)
            ->assertJsonPath('data.days.6.active_accounts', null);
    }

    public function test_profile_combines_recorded_views_and_historical_operations_and_filters_them(): void
    {
        $user = $this->user('مریم', 'رضایی', '09120000001', now()->subYear());
        $other = $this->user('کاربر', 'دیگر', '09120000002', now()->subYear());
        $property = DB::table('advertisements')->insertGetId(['status' => Advertisement::APPROVED]);
        DB::table('advertisement_properties')->insert(['advertisement_id' => $property, 'property_slug' => 'title', 'text_value' => 'آپارتمان آزمایشی']);
        DB::table('mortgage_requests')->insert(['user_id' => $user->id, 'created_at' => now()->subMonth()]);
        foreach ([[$user->id, '/search', null], [$user->id, '/property/[id]', $property], [$other->id, '/search', null]] as [$owner, $page, $propertyId]) {
            DB::table('spad_public_site_page_views')->insert([
                'user_id' => $owner, 'event_key' => Str::uuid()->toString(), 'page' => $page,
                'property_id' => $propertyId, 'created_at' => now(),
            ]);
        }
        $this->admin(true);
        $this->postJson(self::DIRECTORY . '/search')->assertOk()->assertJsonPath('data.summary.signed_in_visitors', 2);
        $profile = self::DIRECTORY . '/' . $user->id;
        $this->getJson($profile)->assertOk()->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.totals.page_views', 1)->assertJsonPath('data.totals.properties', 1);
        $this->getJson($profile . '?kind=property')->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.events.0.property_title', 'آپارتمان آزمایشی')
            ->assertJsonPath('data.events.0.property_url', '/property/' . $property);
        $this->getJson($profile . '?kind=operation&to=2026-11-27')->assertOk()->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.events.0.label', 'ثبت درخواست وام مسکن');
        $this->getJson($profile . '?from=2026-11-28&to=2026-11-28')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson($profile . '?from=2026-11-28&to=2026-11-27')->assertUnprocessable();
    }

    private function admin(bool $superAdmin): void
    {
        $admin = new Admin();
        $admin->id = $superAdmin ? 17 : 18;
        if ($superAdmin) {
            DB::table('roles')->insert(['id' => 1, 'name' => 'Super Admin', 'guard_name' => 'admin']);
            DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => $admin->id, 'model_type' => Admin::class]);
        }
        Sanctum::actingAs($admin);
    }

    private function user(string $firstName, string $lastName, string $mobile, Carbon $createdAt): User
    {
        $id = DB::table('users')->insertGetId([
            'first_name' => $firstName, 'last_name' => $lastName, 'mobile' => $mobile,
            'email' => $mobile . '@example.test', 'password' => 'fixture-only', 'remember_token' => 'fixture-only',
            'created_at' => $createdAt,
        ]);
        return User::query()->findOrFail($id);
    }
}
