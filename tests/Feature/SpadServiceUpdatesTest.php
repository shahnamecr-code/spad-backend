<?php

namespace Tests\Feature;

use App\Http\Middleware\ServiceReceipt;
use App\Http\Controllers\Admin\SpadSmsSettingsController;
use App\Models\Admin;
use App\Models\User;
use App\Services\SpadPropertyPortfolio;
use App\Services\SpadSmsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadServiceUpdatesTest extends TestCase
{
    public function test_sender_trial_uses_the_new_line_and_requires_provider_acceptance(): void
    {
        Http::fake([
            'api.iranpayamak.com/ws/v1/sms/pattern' => Http::sequence()
                ->push(['status' => 'success', 'data' => 728], 201)
                ->push(['status' => 'error'], 200),
        ]);

        $sender = new SpadSmsService();
        $this->assertTrue($sender->testPreferredSender('test-api-key', '09120000000', 'LoginPattern', '123456'));
        $this->assertFalse($sender->testPreferredSender('test-api-key', '09120000000', 'LoginPattern', '123456'));
        Http::assertSent(static function ($request) {
            return $request->hasHeader('Api-Key', 'test-api-key') && $request['line_number'] === '90008242' &&
                $request['recipient'] === '09120000000' && $request['code'] === 'LoginPattern' &&
                ((array) $request['attributes'])['code'] === '123456' && !isset($request['schedule']);
        });
    }

    public function test_enabling_the_preferred_line_requires_a_received_code_and_preserves_existing_settings(): void
    {
        require_once database_path('migrations/2026_11_07_200000_create_spad_sms_centre.php');
        require_once database_path('migrations/2026_11_08_180000_switch_sms_to_iranpayamak.php');
        require_once database_path('migrations/2026_11_08_230000_add_spad_customer_sms_features.php');
        (new \CreateSpadSmsCentre())->up();
        (new \SwitchSmsToIranpayamak())->up();
        (new \AddSpadCustomerSmsFeatures())->up();
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('model_id');
            $table->string('model_type');
        });
        $admin = new Admin();
        $admin->id = 1;
        DB::table('roles')->insert(['id' => 1, 'name' => 'Super Admin', 'guard_name' => 'admin']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => 1, 'model_type' => Admin::class]);
        $events = ['login_otp' => ['enabled' => true, 'pattern_code' => 'LoginPattern']];
        DB::table('spad_sms_settings')->insert([
            'id' => 1, 'enabled' => true, 'sender' => '50008000',
            'api_key_encrypted' => Crypt::encryptString('test-api-key'), 'events_json' => json_encode($events),
        ]);
        $form = [
            'enabled' => true, 'sender' => '90008242', 'daily_limit' => 300,
            'admin_notify_enabled' => false, 'events' => $events,
        ];
        $makeRequest = function (array $data) use ($admin) {
            $request = Request::create('/api/admin/settings/sms', 'POST', $data);
            $request->setUserResolver(fn () => $admin);
            return $request;
        };
        $controller = new SpadSmsSettingsController();
        $this->assertSame(422, $controller->save($makeRequest($form))->getStatusCode());
        $this->assertSame('50008000', DB::table('spad_sms_settings')->value('sender'));

        $receivedCode = null;
        Http::fake(['api.iranpayamak.com/ws/v1/sms/pattern' => function ($request) use (&$receivedCode) {
            $receivedCode = ((array) $request['attributes'])['code'];
            return Http::response(['status' => 'success'], 201);
        }]);
        $trial = $controller->testPreferredSender(
            $makeRequest(['mobile' => '09120000000', 'pattern_code' => 'LoginPattern']), new SpadSmsService()
        );
        $this->assertSame(200, $trial->getStatusCode());
        $this->assertSame('50008000', DB::table('spad_sms_settings')->value('sender'));
        $this->assertSame(422, $controller->save($makeRequest($form + ['sender_test_code' => '000000']))->getStatusCode());
        $this->assertSame(200, $controller->save($makeRequest($form + ['sender_test_code' => $receivedCode]))->getStatusCode());
        $this->assertSame('90008242', DB::table('spad_sms_settings')->value('sender'));
    }

    public function test_zoning_only_displays_verified_municipal_records(): void
    {
        $path = '/api/construction-tools/zoning';
        config(['services.shiraz_zoning.url' => null]);
        Http::fake(['data.shiraz.ir/zoning-test' => Http::response(['data' => [
            'parcel_id' => 'A-21', 'land_use' => 'مسکونی', 'density_percent' => 160,
            'max_floors' => 4, 'source_reference' => 'Municipal-test', 'updated_at' => '2026-11-15',
            'private_field' => 'never-returned',
        ]])]);
        $this->postJson($path, ['method' => 'postal', 'postal_code' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors('postal_code');
        $this->postJson($path, ['method' => 'map', 'lat' => 29.6, 'lng' => 52.5])->assertStatus(503);
        Http::assertNothingSent();

        config(['services.shiraz_zoning.url' => 'https://data.shiraz.ir/zoning-test']);
        $result = $this->postJson($path, ['method' => 'renewal', 'renewal_code' => '12-34-51']);
        $result->assertOk()->assertJsonPath('data.max_floors', 4)->assertHeader('X-Spad-Service-Receipt', 'zoning');
        $this->assertArrayNotHasKey('private_field', $result->json('data'));
    }

    public function test_rent_estimates_use_rent_market_data_and_monthly_toman(): void
    {
        Http::fake([
            'api.nemuda.com/api/market/real-estate/places/22*' => Http::response([
                'place' => [
                    'pricePerM2' => 500000, 'dataPoints' => 27, 'coverage' => 'limited',
                    'computedAt' => '2026-11-25T09:00:00Z',
                    'pricePerM2Range' => ['low' => 400000, 'high' => 650000],
                ],
            ]),
            'api.nemuda.com/api/market/real-estate/place-index' => Http::response(['places' => [
                ['id' => 21, 'name' => 'فارس', 'slug' => 'fars-province'],
                ['id' => 22, 'name' => 'شیراز', 'slug' => 'shiraz', 'parentId' => 21],
            ]]),
        ]);
        $this->getJson('/api/property-market/rent/22?property_kind=apartment&area_m2=90')->assertOk()
            ->assertJsonPath('data.monthly_equivalent_toman', 45000000)
            ->assertJsonPath('data.range_low_toman', 36000000)
            ->assertJsonPath('data.source_url', 'https://nemuda.com/rent/fars-province/shiraz');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'dealType=rent') &&
            str_contains($request->url(), 'propertyKind=apartment'));
    }

    public function test_only_successful_service_submissions_generate_a_receipt(): void
    {
        $middleware = new ServiceReceipt();
        $request = Request::create('/api/online-renovation/3/ai-preview', 'POST');
        $accepted = $middleware->handle($request, fn () => response()->json(['data' => ['id' => 43]], 201));
        $this->assertSame('renovation', $accepted->headers->get('X-Spad-Service-Receipt'));
        foreach ([
            ['status' => false], ['errors' => ['field' => ['message']]], ['already_submitted' => true],
        ] as $body) {
            $rejected = $middleware->handle($request, fn () => response()->json($body));
            $this->assertFalse($rejected->headers->has('X-Spad-Service-Receipt'));
        }
    }

    public function test_account_results_are_private_and_count_outputs_once(): void
    {
        $path = '/api/account/service-results';
        $this->getJson($path)->assertUnauthorized();
        $owner = new User(['status' => User::APPROVED]);
        $owner->id = 17;
        Sanctum::actingAs($owner);

        Schema::create('inquiry_services', function (Blueprint $table) {
            $table->id();
            $table->string('category');
        });
        Schema::create('inquiry_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('inquiry_service_id');
            $table->integer('user_id');
            $table->string('status');
        });
        Schema::create('spad_renovation_projects', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('status');
        });
        Schema::create('spad_renovation_media', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->integer('user_id');
            $table->string('kind');
        });

        DB::table('inquiry_services')->insert(['id' => 1, 'category' => 'قولنامه و کد رهگیری']);
        DB::table('inquiry_requests')->insert([
            ['inquiry_service_id' => 1, 'user_id' => 17, 'status' => 'completed'],
            ['inquiry_service_id' => 1, 'user_id' => 17, 'status' => 'pending'],
            ['inquiry_service_id' => 1, 'user_id' => 18, 'status' => 'completed'],
        ]);
        DB::table('spad_renovation_projects')->insert([
            ['id' => 1, 'user_id' => 17, 'status' => 'draft'],
            ['id' => 2, 'user_id' => 18, 'status' => 'reviewed'],
            ['id' => 3, 'user_id' => 17, 'status' => 'draft'],
        ]);
        DB::table('spad_renovation_media')->insert([
            ['project_id' => 1, 'user_id' => 17, 'kind' => 'output'],
            ['project_id' => 1, 'user_id' => 17, 'kind' => 'output'],
            ['project_id' => 3, 'user_id' => 18, 'kind' => 'output'],
        ]);

        $result = $this->getJson($path);
        $result->assertOk()->assertExactJson(['data' => [
            'inquiries' => 1, 'contracts' => 1, 'renovation' => 1, 'propertyAffairs' => 0,
        ]]);
        $this->assertStringContainsString('no-store', (string) $result->headers->get('Cache-Control'));
    }

    public function test_portfolio_preserves_old_sale_receipts_and_rent_is_idempotent(): void
    {
        require_once database_path('migrations/2026_11_05_160000_create_spad_user_property_portfolio_tables.php');
        require_once database_path('migrations/2026_11_08_140000_create_property_acceptance_requests.php');
        require_once database_path('migrations/2026_11_24_150000_add_deal_type_to_spad_property_acceptance.php');
        (new \CreateSpadUserPropertyPortfolioTables())->up();
        (new \CreatePropertyAcceptanceRequests())->up();
        (new \AddDealTypeToSpadPropertyAcceptance())->up();

        $owner = new User(['status' => User::APPROVED]);
        $owner->id = 17;
        Sanctum::actingAs($owner);
        $id = DB::table('spad_user_property_assets')->insertGetId([
            'user_id' => 17, 'postal_hash' => str_repeat('a', 64), 'postal_cipher' => Crypt::encryptString('1234567890'),
            'address_cipher' => Crypt::encryptString('{}'), 'nickname' => 'خانهٔ من', 'province' => 'فارس',
            'city' => 'شیراز', 'place_id' => 22, 'place_name' => 'شیراز', 'match_level' => 'city',
            'property_kind' => 'apartment', 'area_m2' => 90, 'estimated_total_toman' => 9000000000,
        ]);
        $asset = DB::table('spad_user_property_assets')->where('id', $id)->first();
        $legacy = DB::table('spad_property_acceptance_requests')->insertGetId([
            'user_id' => 17, 'quote_hash' => SpadPropertyPortfolio::requestHashes($asset, 'sell')[1],
            'postal_code_encrypted' => $asset->postal_cipher, 'property_kind' => 'apartment', 'area_m2' => 90,
            'estimated_total_toman' => 9000000000, 'market_source' => 'نمودا',
            'valuation_snapshot_encrypted' => Crypt::encryptString('{}'), 'status' => 'under_review',
        ]);
        $path = '/api/my-property-assets/'.$id;
        $consent = ['owner_confirmed' => true, 'contact_consent' => true];
        $this->postJson($path.'/sale-request', $consent)->assertOk()->assertJsonPath('data.id', $legacy)
            ->assertJsonPath('data.status', 'under_review');
        $rent = $this->postJson($path.'/acceptance', $consent + ['deal_type' => 'rent']);
        $rent->assertCreated()->assertHeader('X-Spad-Service-Receipt', 'property_request')->assertJsonPath('data.deal_type', 'rent');
        $this->postJson($path.'/acceptance', $consent + ['deal_type' => 'rent'])->assertOk()
            ->assertJsonPath('data.id', $rent->json('data.id'))->assertJsonPath('already_submitted', true);
        $this->assertDatabaseCount('spad_property_acceptance_requests', 2);

        $portfolio = (new SpadPropertyPortfolio())->present($asset);
        $this->assertSame($legacy, $portfolio['sale_request']['id']);
        $this->assertSame($rent->json('data.id'), $portfolio['rent_request']['id']);
        $this->assertLessThanOrEqual(20000000, abs($portfolio['daily_scenario']['change_toman']));

        $outsider = new User(['status' => User::APPROVED]);
        $outsider->id = 18;
        Sanctum::actingAs($outsider);
        $this->postJson($path.'/acceptance', $consent + ['deal_type' => 'rent'])->assertNotFound();
    }
}
