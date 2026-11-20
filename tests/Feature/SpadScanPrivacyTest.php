<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadScanPrivacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) $this->markTestSkipped('PDO SQLite is required for an isolated database.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        require_once database_path('migrations/2026_11_20_160000_create_spad_scan_tables.php');
        require_once database_path('migrations/2026_11_20_160001_create_spad_home_service_operations.php');
        (new \CreateSpadScanTables())->up();
        (new \CreateSpadHomeServiceOperations())->up();
        Storage::fake('local');
    }

    public function test_only_owner_can_read_or_delete_a_plan_and_its_media(): void
    {
        $scan = DB::table('spad_scans')->insertGetId([
            'user_id' => 45, 'title' => 'نقشه خصوصی', 'rooms' => '[]',
            'room_count' => 0, 'photo_count' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $path = 'spad-scan/' . $scan . '/test.png';
        Storage::disk('local')->put($path, 'private photo bytes');
        $photo = DB::table('spad_scan_photos')->insertGetId([
            'scan_id' => $scan, 'name' => 'عکس', 'path' => $path, 'mime_type' => 'image/png',
            'size_bytes' => 19, 'created_at' => now(),
        ]);
        $url = '/api/online-renovation/scans/' . $scan;

        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs((new User())->forceFill(['id' => 46]));
        $this->getJson('/api/online-renovation/scans')->assertJson(['data' => []]);
        $this->getJson($url)->assertNotFound();
        $this->getJson($url . '/photos/' . $photo)->assertNotFound();
        $this->deleteJson($url . '/photos/' . $photo)->assertNotFound();

        Sanctum::actingAs((new User())->forceFill(['id' => 45]));
        $this->getJson($url)->assertOk()->assertJsonPath('data.photos.0.id', $photo)
            ->assertJsonMissingPath('data.photos.0.path');
        $media = $this->getJson($url . '/photos/' . $photo)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', $media->headers->get('Cache-Control'));
        $this->assertSame('private photo bytes', $media->streamedContent());

        $this->deleteJson($url)->assertNoContent();
        Storage::disk('local')->assertMissing($path);
        $this->getJson($url)->assertNotFound();
    }
}
