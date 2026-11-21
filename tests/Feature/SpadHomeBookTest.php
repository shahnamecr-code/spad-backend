<?php

namespace Tests\Feature;

use App\Http\Middleware\SpadHomeService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomeBookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(SpadHomeService::class);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->timestamps();
            $table->softDeletes();
        });
        require_once database_path('migrations/2026_11_21_133000_create_spad_homebook.php');
        (new \CreateSpadHomebook())->up();
        Storage::fake('local');
    }

    public function test_private_inventory_warranty_and_repair_round_trip()
    {
        $path = '/api/home-book';
        $this->getJson($path)->assertUnauthorized();
        $owner = User::forceCreate(['status' => User::APPROVED]);
        $outsider = User::forceCreate(['status' => User::APPROVED]);
        Sanctum::actingAs($owner);
        $home = $this->postJson($path.'/homes', ['name' => 'خانه'])->assertCreated()->json('data.id');
        $today = now('Asia/Tehran');
        $input = ['home_id' => $home, 'name' => 'یخچال', 'category' => 'cold', 'serial' => 'AB18001',
            'warranty_started_on' => $today->toDateString(), 'warranty_ends_on' => $today->copy()->addDays(10)->toDateString()];
        $device = $this->postJson($path.'/devices', $input)->assertCreated()->assertJsonPath('data.warranty_status', 'expiring')->json('data.id');
        $this->assertNotSame('AB18001', DB::table('spad_homebook_devices')->where('id', $device)->value('serial'));
        $record = $path.'/devices/'.$device;
        $index = $this->getJson($path)->assertOk()->assertJsonPath('data.warranties.expiring', 1);
        $this->assertArrayNotHasKey('serial', $index->json('data.devices.0'));
        $this->getJson($record)->assertOk()->assertJsonPath('data.serial', 'AB18001');
        $this->deleteJson($path.'/homes/'.$home)->assertStatus(409);
        $repair = $this->postJson($record.'/maintenance', ['title' => 'سرویس', 'kind' => 'service', 'service_on' => $today->toDateString()])
            ->assertCreated()->json('data.maintenance.0.id');
        $this->patchJson($record.'/maintenance/'.$repair, ['completed' => true])->assertOk();
        $this->assertNotNull(DB::table('spad_homebook_maintenance')->where('id', $repair)->value('completed_at'));

        Sanctum::actingAs($outsider);
        $this->getJson($record)->assertNotFound();
        $this->getJson($record.'/documents/1')->assertNotFound();
        $this->postJson($path.'/devices', $input)->assertNotFound();
        $this->deleteJson($record)->assertNotFound();
        Sanctum::actingAs($owner);
        $this->deleteJson($record)->assertNoContent();
        $this->assertDatabaseMissing('spad_homebook_maintenance', ['id' => $repair]);
    }

    public function test_document_content_and_ocr_consent_are_validated()
    {
        $user = User::forceCreate(['status' => User::APPROVED]);
        Sanctum::actingAs($user);
        $path = '/api/home-book';
        $home = $this->postJson($path.'/homes', ['name' => 'خانه'])->assertCreated()->json('data.id');
        $device = $this->postJson($path.'/devices', ['home_id' => $home, 'name' => 'اجاق', 'category' => 'oven'])->assertCreated()->json('data.id');
        $fake = UploadedFile::fake()->createWithContent('false.pdf', 'Not a document');
        $this->post($path.'/devices/'.$device.'/documents', ['title' => 'مدرک', 'kind' => 'invoice', 'file' => $fake], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->postJson($path.'/label', ['consent' => false])->assertUnprocessable()->assertJsonValidationErrors('consent');
    }
}
