<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpadPortfolioSaleTest extends TestCase
{
    private const CONSENT = ['owner_confirmed' => true, 'contact_consent' => true];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        require_once database_path('migrations/2026_11_05_160000_create_spad_user_property_portfolio_tables.php');
        (new \CreateSpadUserPropertyPortfolioTables())->up();
        require_once database_path('migrations/2026_11_08_140000_create_property_acceptance_requests.php');
        (new \CreatePropertyAcceptanceRequests())->up();
    }

    private function user(): User
    {
        $id = DB::table('users')->insertGetId(['first_name' => 'مالک', 'last_name' => 'آزمایشی']);
        return User::query()->findOrFail($id);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::query()->forceCreate(['first_name' => 'مدیر', 'username' => 'root', 'status' => Admin::APPROVED]);
        $role = DB::table('roles')->insertGetId(['name' => 'Super Admin', 'guard_name' => 'admin']);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => Admin::class, 'model_id' => $admin->id]);
        return $admin;
    }

    private function addAsset(User $user, ?int $estimate = 8400000000): int
    {
        return DB::table('spad_user_property_assets')->insertGetId([
            'user_id' => $user->id, 'postal_hash' => str_repeat('a', 64),
            'postal_cipher' => Crypt::encryptString('1111111111'),
            'address_cipher' => Crypt::encryptString(json_encode(['address' => 'نشانی آزمایشی'])),
            'nickname' => 'ملک آزمایشی', 'province' => 'اصفهان', 'city' => 'اصفهان',
            'place_id' => 42, 'place_name' => 'اصفهان', 'match_level' => 'city',
            'property_kind' => 'apartment', 'area_m2' => 120,
            'estimated_total_toman' => $estimate, 'price_per_m2_toman' => 70000000,
            'coverage' => 'solid', 'last_checked_at' => now(), 'last_success_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_sale_requires_an_authenticated_asset_owner_and_both_consents(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $id = $this->addAsset($owner);
        $path = '/api/my-property-assets/' . $id . '/sale-request';

        $this->postJson($path, self::CONSENT)->assertUnauthorized();
        $this->actingAs($other, 'sanctum')->postJson($path, self::CONSENT)->assertNotFound();
        $this->actingAs($owner, 'sanctum')->postJson($path, ['owner_confirmed' => true])->assertStatus(422);
        $this->actingAs($owner, 'sanctum')->postJson($path, ['contact_consent' => true])->assertStatus(422);
        $this->assertDatabaseCount('spad_property_acceptance_requests', 0);
    }

    public function test_portfolio_sale_creates_one_crm_case_and_reports_live_status(): void
    {
        $owner = $this->user();
        $assetId = $this->addAsset($owner);
        $path = '/api/my-property-assets/' . $assetId . '/sale-request';
        $this->actingAs($owner, 'sanctum')->getJson('/api/my-property-assets')
            ->assertOk()->assertJsonPath('data.items.0.sale_request', null);

        $id = $this->postJson($path, self::CONSENT + ['estimated_total_toman' => 1])
            ->assertCreated()->assertJsonPath('data.status', 'new')->json('data.id');
        $this->postJson($path, self::CONSENT)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/property-acceptance/mine')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->assertDatabaseCount('spad_property_acceptance_requests', 1);
        $this->assertDatabaseCount('spad_property_acceptance_activity', 1);
        $this->assertDatabaseHas('spad_property_acceptance_requests', [
            'id' => $id, 'user_id' => $owner->id, 'city' => 'اصفهان', 'estimated_total_toman' => 8400000000,
            'owner_confirmed' => true, 'contact_consent' => true,
        ]);

        $this->actingAs($this->superAdmin(), 'admin')->getJson('/api/admin/property-acceptance')
            ->assertOk()->assertJsonPath('data.data.0.id', $id);
        $this->getJson('/api/admin/property-acceptance/' . $id)->assertOk()
            ->assertJsonPath('data.valuation.origin', 'portfolio')
            ->assertJsonPath('data.valuation.asset_id', $assetId)
            ->assertJsonPath('data.valuation.asset_nickname', 'ملک آزمایشی')
            ->assertJsonPath('data.postal_code', '1111111111');
        $this->postJson('/api/admin/property-acceptance/' . $id, ['status' => 'contacted'])->assertOk();
        $this->actingAs($owner, 'sanctum')->getJson('/api/my-property-assets')->assertOk()
            ->assertJsonPath('data.items.0.sale_request.id', $id)
            ->assertJsonPath('data.items.0.sale_request.status', 'contacted')
            ->assertJsonPath('data.items.0.sale_request.status_label', 'تماس انجام شد');
        $this->postJson($path, self::CONSENT)->assertOk()
            ->assertJsonPath('data.id', $id)->assertJsonPath('data.status', 'contacted');
    }

    public function test_missing_valuation_does_not_create_a_sale_case(): void
    {
        $owner = $this->user();
        $asset = $this->addAsset($owner, null);
        $this->actingAs($owner, 'sanctum')->postJson('/api/my-property-assets/' . $asset . '/sale-request', self::CONSENT)
            ->assertStatus(422);
        $this->assertDatabaseCount('spad_property_acceptance_requests', 0);
    }

    public function test_a_legacy_quote_still_enters_the_same_workflow(): void
    {
        $owner = $this->user();
        $quote = str_repeat('q', 48);
        Cache::put('spad:asset:quote:' . $quote, [
            'user_id' => $owner->id, 'postal_code' => '1111111111', 'city' => 'شیراز', 'province' => 'فارس',
            'valuation' => ['property_kind' => 'apartment', 'area_m2' => 120, 'estimated_total_toman' => 8400000000],
        ], 60);
        $payload = self::CONSENT + ['quote_token' => $quote];
        $id = $this->actingAs($owner, 'sanctum')->postJson('/api/property-acceptance/submit', $payload)
            ->assertCreated()->json('data.id');
        $this->postJson('/api/property-acceptance/submit', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('spad_property_acceptance_activity', 1);
    }
}
