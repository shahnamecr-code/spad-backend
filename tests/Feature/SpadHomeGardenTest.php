<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomeGardenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('spad_app_notifications', function (Blueprint $table) { $table->id(); });
        require_once database_path('migrations/2026_11_20_160001_create_spad_home_service_operations.php');
        require_once database_path('migrations/2026_11_21_190000_add_spad_household_services_settings.php');
        require_once database_path('migrations/2026_11_21_190001_create_spad_household_reminders.php');
        require_once database_path('migrations/2026_11_21_170100_create_spad_home_garden_tables.php');
        (new \CreateSpadHomeServiceOperations())->up();
        (new \AddSpadHouseholdServicesSettings())->up();
        (new \CreateSpadHouseholdReminders())->up();
        (new \CreateSpadHomeGardenTables())->up();
        Carbon::setTestNow('2026-11-21 12:00:00 UTC');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_garden_reminder_is_opt_in_idempotent_and_tracks_water_and_snooze()
    {
        $owner = User::forceCreate(['status' => User::APPROVED]);
        Sanctum::actingAs($owner);
        $plant = $this->postJson('/api/home-garden/plants', [
            'name' => 'پتوس', 'water_every_days' => 4, 'first_watering_on' => '2026-11-22',
        ])->assertCreated()->assertJsonPath('data.reminder_enabled', false)->json('data');
        $root = '/api/home-garden/plants/' . $plant['id'];
        $this->assertSame(0, DB::table('spad_household_reminders')->count());
        $this->patchJson($root, ['reminder_enabled' => true])->assertOk()->assertJsonPath('data.reminder_status', 'scheduled');
        $this->patchJson($root, ['reminder_enabled' => true])->assertOk();
        $schedule = DB::table('spad_household_reminders')->first();
        $this->assertSame(1, DB::table('spad_household_reminders')->count());
        $snooze = ['cycle' => $plant['water_cycle'], 'action' => 'snoozed', 'snooze_days' => 2, 'confirmation' => true];
        $after = $this->postJson($root.'/water', $snooze)->assertOk()->json('data');
        $this->postJson($root.'/water', $snooze)->assertOk()->assertJsonPath('data.water_cycle', $after['water_cycle']);
        $this->assertNotSame($plant['water_cycle'], $after['water_cycle']);
        $this->assertSame('2026-11-23 20:30:00', DB::table('spad_household_reminders')->where('id', $schedule->id)->value('due_at'));
        $this->postJson($root.'/water', ['cycle' => $plant['water_cycle'], 'action' => 'watered', 'confirmation' => true])->assertStatus(409);
        $this->postJson($root.'/water', ['cycle' => $after['water_cycle'], 'action' => 'watered'])->assertUnprocessable();
        $this->postJson($root.'/water', ['cycle' => $after['water_cycle'], 'action' => 'watered', 'confirmation' => true])->assertOk();
        $this->assertSame('2026-11-25 12:00:00', DB::table('spad_household_reminders')->where('id', $schedule->id)->value('due_at'));
        $this->assertSame(2, DB::table('spad_garden_activity')->whereNotNull('water_cycle')->count());
        $this->patchJson($root, ['archived' => true])->assertOk()->assertJsonPath('data.reminder_status', null);
        $this->assertSame(0, DB::table('spad_household_reminders')->count());
        $this->patchJson($root, ['archived' => false])->assertOk()->assertJsonPath('data.reminder_status', 'scheduled');
        $this->patchJson($root, ['reminder_enabled' => false])->assertOk()->assertJsonPath('data.reminder_enabled', false);
        $this->assertSame(0, DB::table('spad_household_reminders')->count());
    }

    public function test_private_photo_and_species_require_correct_owner_and_confirmation()
    {
        Storage::fake('local');
        Http::fake();
        $owner = User::forceCreate(['status' => User::APPROVED]);
        $other = User::forceCreate(['status' => User::APPROVED]);
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/home-garden/plants', ['name' => 'گیاه', 'water_every_days' => 7])->assertCreated()->json('data.id');
        $path = '/api/home-garden/plants/' . $id;
        $photo = (string) Str::uuid();
        $storage = "home-garden/$id/$photo.jpg";
        Storage::disk('local')->put($storage, 'private-photo');
        DB::table('spad_garden_photos')->insert([
            'id' => $photo, 'plant_id' => $id, 'stage' => 'start', 'path' => $storage, 'created_at' => now('UTC'),
        ]);
        $route = $path.'/photos/'.$photo;
        $photoResponse = $this->getJson($route)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('private-photo', $photoResponse->getContent());
        $this->postJson($route.'/identify', ['consent' => false])->assertUnprocessable();
        $this->postJson($route.'/identify', ['consent' => true])->assertOk()->assertJsonPath('data.status', 'unavailable');
        $this->putJson($path.'/species', ['species' => 'پتوس'])->assertUnprocessable();
        $this->putJson($path.'/species', ['species' => 'پتوس', 'confirmation' => true])->assertOk()->assertJsonPath('data.species_confirmed', true);
        Sanctum::actingAs($other);
        $this->getJson($path)->assertNotFound();
        $this->getJson($route)->assertNotFound();
        $this->postJson($route.'/identify', ['consent' => true])->assertNotFound();
        $this->deleteJson($route)->assertNotFound();
        $this->getJson('/api/home-garden/plants')->assertOk()->assertJsonCount(0, 'data');
        Http::assertNothingSent();
    }
}
