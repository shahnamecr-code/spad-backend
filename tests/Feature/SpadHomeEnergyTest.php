<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomeEnergyTest extends TestCase
{
    private const ROOT = '/api/home-energy';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-21 12:00:00', 'Asia/Tehran'));
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        require_once database_path('migrations/2026_11_21_133500_create_spad_home_energy.php');
        (new \CreateSpadHomeEnergy())->up();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function login(): User
    {
        $user = User::forceCreate(['mobile' => '09120000000']);
        Sanctum::actingAs($user);
        return $user;
    }

    private function location(string $title = 'خانه شخصی'): int
    {
        return $this->postJson(self::ROOT . '/locations', ['title' => $title])->assertCreated()->json('data.id');
    }

    private function account(int $location, string $identifier = '۰۱۲۳۴۵۶۷۸۹'): int
    {
        return $this->postJson(self::ROOT . '/accounts', [
            'title' => 'آب خانه', 'location_id' => $location, 'utility' => 'water', 'identifier' => $identifier,
        ])->assertCreated()->assertJsonPath('data.identifier', '0123456789')->json('data.id');
    }

    private function bill(int $account, array $data): int
    {
        return $this->postJson(self::ROOT . '/accounts/' . $account . '/bills', $data)
            ->assertCreated()->json('data.id');
    }

    public function test_saved_bills_report_real_owner_totals_and_comparable_daily_consumption()
    {
        $this->getJson(self::ROOT . '/dashboard')->assertUnauthorized();
        $this->login();
        $location = $this->location();
        $account = $this->account($location);
        $futureDue = now('Asia/Tehran')->addDays(10)->toDateString();
        $today = now('Asia/Tehran')->toDateString();
        $older = [
            'amount_toman' => 1000, 'consumption' => 15.5,
            'period_start' => '2026-07-01', 'period_end' => '2026-07-30',
            'paid_on' => '2026-09-01', 'payment_identifier' => '۰۰۹۰۹۰۹۰',
        ];
        $latest = [
            'amount_toman' => 1600, 'consumption' => 31,
            'period_start' => '2026-08-01', 'period_end' => '2026-08-30',
            'due_on' => $futureDue, 'remind_on' => $today,
        ];
        $first = $this->bill($account, $older);
        $second = $this->bill($account, $latest);

        $this->postJson(self::ROOT . '/accounts/' . $account . '/bills', $latest)
            ->assertOk()->assertJsonPath('data.id', $second);
        $this->getJson(self::ROOT . '/dashboard')->assertOk()
            ->assertJsonPath('data.totals.bill_count', 2)
            ->assertJsonPath('data.totals.unpaid_count', 1)
            ->assertJsonPath('data.totals.unpaid_toman', 1600)
            ->assertJsonPath('data.totals.next_due.id', $second)
            ->assertJsonPath('data.reminders.0.id', $second);
        $detail = $this->getJson(self::ROOT . '/accounts/' . $account)->assertOk()
            ->assertJsonPath('data.comparison.current_days', 30)
            ->assertJsonPath('data.comparison.previous_days', 30)
            ->assertJsonPath('data.comparison.cost_change_percent', 60)
            ->assertJsonPath('data.comparison.consumption_daily_change_percent', 100)
            ->assertJsonPath('data.bills.1.payment_status', 'owner_recorded');
        $this->assertNull($detail->json('data.bills.0.paid_on'));
        $this->assertNotSame('0123456789', DB::table('spad_home_energy_accounts')->value('identifier_encrypted'));
        $this->assertNotSame('00909090', DB::table('spad_home_energy_bills')->where('id', $first)->value('payment_identifier_encrypted'));

        $bad = array_merge($latest, ['period_start' => '2026-09-03', 'period_end' => '2026-09-01']);
        $this->postJson(self::ROOT . '/accounts/' . $account . '/bills', $bad)->assertStatus(422);
        $this->deleteJson(self::ROOT . '/locations/' . $location)->assertStatus(409);
    }

    public function test_other_accounts_cannot_read_change_or_copy_private_bill_records()
    {
        $owner = $this->login();
        $location = $this->location();
        $account = $this->account($location);
        $bill = $this->bill($account, ['amount_toman' => 1200, 'issued_on' => '2026-10-01']);

        $this->login();
        $this->getJson(self::ROOT . '/dashboard')->assertOk()->assertJsonCount(0, 'data.accounts');
        $this->getJson(self::ROOT . '/accounts/' . $account)->assertNotFound();
        $this->patchJson(self::ROOT . '/bills/' . $bill, ['amount_toman' => 2000, 'issued_on' => '2026-10-01'])->assertNotFound();
        $this->deleteJson(self::ROOT . '/accounts/' . $account)->assertNotFound();
        $this->getJson(self::ROOT . '/bills/' . $bill . '/receipt')->assertNotFound();
        $this->postJson(self::ROOT . '/accounts', [
            'location_id' => $location, 'title' => 'آب', 'utility' => 'water', 'identifier' => '1234567',
        ])->assertNotFound();

        Sanctum::actingAs($owner);
        $this->patchJson(self::ROOT . '/bills/' . $bill, [
            'amount_toman' => 1300, 'issued_on' => '2026-10-01', 'paid_on' => '2026-10-02',
        ])->assertOk()->assertJsonPath('data.payment_status', 'owner_recorded');
        $this->getJson(self::ROOT . '/dashboard')->assertOk()->assertJsonPath('data.totals.unpaid_count', 0);
    }

    public function test_building_import_uses_active_owned_units_and_explicit_consent()
    {
        Schema::create('spad_buildings', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('spad_building_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('user_id');
            $table->string('label');
            $table->string('status');
            $table->string('water_bill_id')->nullable();
            $table->string('electricity_bill_id')->nullable();
            $table->string('gas_bill_id')->nullable();
            $table->string('landline_bill_id')->nullable();
        });
        $owner = $this->login();
        $other = User::forceCreate(['mobile' => '09120000009']);
        $location = $this->location();
        $building = DB::table('spad_buildings')->insertGetId(['name' => 'ساختمان تمرین']);
        $unit = DB::table('spad_building_units')->insertGetId([
            'building_id' => $building, 'user_id' => $owner->id, 'status' => 'active', 'label' => 'واحد یک', 'water_bill_id' => '0011223344',
        ]);
        $foreign = DB::table('spad_building_units')->insertGetId([
            'building_id' => $building, 'user_id' => $other->id, 'status' => 'active', 'label' => 'واحد دو', 'water_bill_id' => '0099223344',
        ]);
        DB::table('spad_building_units')->insert([
            'building_id' => $building, 'user_id' => $owner->id, 'status' => 'pending', 'label' => 'واحد سه', 'water_bill_id' => '0077223344',
        ]);
        $this->getJson(self::ROOT . '/building-sources')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_four', '3344')->assertDontSee('0011223344');
        $request = ['unit_id' => $unit, 'location_id' => $location, 'utility' => 'water'];
        $this->postJson(self::ROOT . '/building-import', $request)->assertStatus(422);
        $this->postJson(self::ROOT . '/building-import', array_merge($request, ['unit_id' => $foreign, 'consent' => true]))->assertNotFound();
        $this->postJson(self::ROOT . '/building-import', $request + ['consent' => true])->assertCreated()
            ->assertJsonPath('data.identifier', '0011223344')->assertJsonPath('data.source', 'building_unit');
        $this->postJson(self::ROOT . '/building-import', $request + ['consent' => true])->assertOk();
        $this->getJson(self::ROOT . '/dashboard')->assertJsonCount(1, 'data.accounts');
    }

    public function test_receipts_are_private_uploaded_images_that_are_deleted_with_bills()
    {
        $owner = $this->login();
        $account = $this->account($this->location());
        $bill = $this->bill($account, ['amount_toman' => 1200, 'issued_on' => '2026-10-01']);
        $path = self::ROOT . '/bills/' . $bill . '/receipt';
        $this->post($path, ['image' => UploadedFile::fake()->image('receipt.jpg', 100, 100)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.has_receipt', true);
        $receipt = $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertTrue($receipt->headers->hasCacheControlDirective('no-store'));
        $file = DB::table('spad_home_energy_bills')->where('id', $bill)->value('receipt_path');
        Storage::disk('local')->assertExists($file);
        $this->login();
        $this->getJson($path)->assertNotFound();
        $this->deleteJson($path)->assertNotFound();
        Sanctum::actingAs($owner);
        $this->deleteJson(self::ROOT . '/bills/' . $bill)->assertNoContent();
        Storage::disk('local')->assertMissing($file);
    }

    public function test_capability_check_distinguishes_saved_configuration_from_operational_payment()
    {
        Http::fake();
        Schema::create('spad_web_service_connections', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->boolean('enabled');
            $table->string('mode');
            $table->string('provider')->nullable();
        });
        $this->getJson(self::ROOT . '/capabilities')->assertOk()->assertJsonPath('data.inquiry_configured', false);
        DB::table('spad_web_service_connections')->insert([
            'code' => 'utility-bills', 'enabled' => true, 'mode' => 'configured', 'provider' => 'درگاه نامشخص',
        ]);
        $this->getJson(self::ROOT . '/capabilities')->assertOk()->assertJsonPath('data.inquiry_configured', true)
            ->assertJsonPath('data.payment_enabled', false)->assertJsonPath('data.inquiry_operational', null);
        Http::assertNothingSent();
    }
}
