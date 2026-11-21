<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SpadSunlight;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadSunlightPrivacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('spad_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->json('rooms');
        });
        require_once database_path('migrations/2026_11_20_160001_create_spad_home_service_operations.php');
        require_once database_path('migrations/2026_11_21_190000_add_spad_household_services_settings.php');
        require_once database_path('migrations/2026_11_21_135000_create_spad_sunlight_projects.php');
        (new \CreateSpadHomeServiceOperations())->up();
        (new \AddSpadHouseholdServicesSettings())->up();
        (new \CreateSpadSunlightProjects())->up();
    }

    public function test_public_calculation_does_not_store_location_and_project_routes_scope_to_owner(): void
    {
        $api = '/api/home-sunlight';
        $input = SpadSunlight::example();
        $this->getJson($api . '/example')->assertOk()->assertJsonPath('data.educational', true);
        $public = $this->postJson($api . '/analyze', $input)->assertOk()->json('data.export');
        $this->assertArrayNotHasKey('latitude', $public);
        $this->assertArrayNotHasKey('solar', $public);
        $this->assertSame(0, DB::table('spad_sunlight_projects')->count());
        $this->getJson($api . '/projects')->assertUnauthorized();

        $owner = User::forceCreate(['first_name' => 'سارا', 'status' => User::APPROVED]);
        $other = User::forceCreate(['first_name' => 'امید', 'status' => User::APPROVED]);
        Sanctum::actingAs($owner);
        $payload = ['title' => 'پذیرایی', 'input' => $input];
        $project = $this->postJson($api . '/projects', $payload)->assertCreated()->json('data.id');
        $root = $api . '/projects/' . $project;
        $this->getJson($root)->assertOk()->assertJsonPath('data.input.latitude', $input['latitude']);

        $scan = DB::table('spad_scans')->insertGetId(['user_id' => $owner->id, 'rooms' => json_encode([[
            'name' => 'اتاق نمونه', 'height' => 3, 'points' => [['x' => 0, 'y' => 0], ['x' => 4, 'y' => 0], ['x' => 4, 'y' => 3], ['x' => 0, 'y' => 3]],
            'openings' => [['type' => 'window', 'wall_index' => 2, 'offset' => 1, 'width' => .8, 'sill' => 1, 'height' => 1]],
        ]])]);
        $imported = $this->getJson($api . '/scan/' . $scan)->assertOk()->assertJsonPath('data.rooms.0.windows.0.wall', 'back')
            ->assertJsonPath('data.rooms.0.orientation_required', true);
        $this->assertArrayNotHasKey('orientation_deg', $imported->json('data.rooms.0.room'));

        Sanctum::actingAs($other);
        $this->getJson($root)->assertNotFound();
        $this->putJson($root, $payload)->assertNotFound();
        $this->deleteJson($root)->assertNotFound();
        $this->getJson($api . '/scan/' . $scan)->assertNotFound();
        Sanctum::actingAs($owner);
        $this->deleteJson($root)->assertNoContent();
    }
}
