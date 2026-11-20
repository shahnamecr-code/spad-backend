<?php

namespace Tests\Feature;

use App\Http\Middleware\SpadHomeService;
use App\Http\Middleware\SpadHomeServiceAdministrator;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomeLeagueTest extends TestCase
{
    private const CLOCK = 'Asia/Tehran';
    private const BASE = '/api/home-league';
    private const CONTESTS = 'spad_home_league_contests';
    private const ENTRIES = 'spad_home_league_entries';
    private const VOTES = 'spad_home_league_votes';
    private const PHOTOS = 'spad_home_league_photos';

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for an isolated database.');
        }

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        require_once database_path('migrations/2026_11_20_160000_create_spad_home_league_tables.php');
        (new \CreateSpadHomeLeagueTables())->up();
        Storage::fake('local');
        $this->withoutMiddleware(SpadHomeService::class);
        $this->clock('2026-11-20 23:55:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_vote_is_unique_per_user_and_contest_until_midnight_in_tehran(): void
    {
        $contest = $this->contest();
        $first = $this->entry($contest, 10, 'approved');
        $second = $this->entry($contest, 11, 'approved');
        $this->asCustomer(50);

        $this->postJson(self::BASE . '/fall/entries/' . $first . '/vote')->assertCreated();
        $this->postJson(self::BASE . '/fall/entries/' . $second . '/vote')->assertStatus(409);
        $this->getJson(self::BASE . '/fall/my-vote')->assertJsonPath('data.entry_id', $first);
        $this->assertSame(['2026-11-20'], DB::table(self::VOTES)->pluck('vote_date')->all());

        $this->clock('2026-11-21 00:01:00');
        $this->assertSame('2026-11-20', Carbon::now('UTC')->toDateString());
        $this->getJson(self::BASE . '/fall/my-vote')->assertJsonPath('data', null);
        $this->postJson(self::BASE . '/fall/entries/' . $second . '/vote')->assertCreated();
        $this->assertSame(['2026-11-20', '2026-11-21'], DB::table(self::VOTES)->orderBy('id')->pluck('vote_date')->all());

        $this->expectException(QueryException::class);
        DB::table(self::VOTES)->insert([
            'contest_id' => $contest, 'entry_id' => $first, 'user_id' => 50,
            'vote_date' => '2026-11-21', 'valid' => true,
        ]);
    }

    public function test_voting_requires_an_approved_entry_and_an_open_window(): void
    {
        $contest = $this->contest();
        $approved = $this->entry($contest, 10, 'approved');
        $pending = $this->entry($contest, 11, 'pending');
        $this->asCustomer(50);

        $this->clock('2026-11-20 11:59:59');
        $this->postJson(self::BASE . '/fall/entries/' . $approved . '/vote')->assertStatus(422);
        $this->clock('2026-11-20 12:00:00');
        $this->postJson(self::BASE . '/fall/entries/' . $pending . '/vote')->assertNotFound();
        $this->clock('2026-11-21 12:00:00');
        $this->postJson(self::BASE . '/fall/entries/' . $approved . '/vote')->assertStatus(422);
        $this->assertSame(0, DB::table(self::VOTES)->count());
    }

    public function test_pending_photos_and_entry_data_require_ownership_and_never_use_public_urls(): void
    {
        $contest = $this->contest();
        $entry = $this->entry($contest, 10, 'pending');
        $other = $this->entry($contest, 11, 'approved');
        $path = 'private/spad-home-league/sample.jpg';
        Storage::disk('local')->put($path, 'opaque private image fixture');
        $photo = DB::table(self::PHOTOS)->insertGetId(['entry_id' => $entry, 'position' => 0, 'path' => $path]);

        $publicEntry = self::BASE . '/fall/entries/' . $entry;
        $mine = self::BASE . '/my-entries/' . $entry;
        $publicPhoto = $publicEntry . '/photos/' . $photo;
        $ownerPhoto = $mine . '/photos/' . $photo;
        $this->getJson(self::BASE . '/fall')->assertOk()->assertJsonCount(1, 'data.entries')
            ->assertJsonPath('data.entries.0.id', $other);
        $this->getJson($publicEntry)->assertNotFound();
        $this->getJson($publicPhoto)->assertNotFound();
        $this->getJson($ownerPhoto)->assertUnauthorized();

        $this->asCustomer(11);
        $this->getJson($mine)->assertNotFound();
        $this->getJson($ownerPhoto)->assertNotFound();
        $this->deleteJson($publicEntry)->assertNotFound();

        $this->asCustomer(10);
        $details = $this->getJson($mine)->assertOk()
            ->assertJsonPath('data.entry.status', 'pending')
            ->assertJsonPath('data.entry.photos.0.id', $photo);
        $this->assertArrayNotHasKey('path', $details->json('data.entry.photos.0'));
        $this->assertArrayNotHasKey('user_id', $details->json('data.entry'));
        $this->getJson($publicPhoto)->assertNotFound();
        $image = $this->get($ownerPhoto)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('no-store', $image->headers->get('Cache-Control'));
        $this->assertSame('opaque private image fixture', $image->streamedContent());

        DB::table(self::ENTRIES)->where('id', $entry)->update(['status' => 'approved']);
        $this->get($publicPhoto)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->deleteJson($publicEntry)->assertOk();
        $this->getJson($publicPhoto)->assertNotFound();
    }

    public function test_photo_upload_persists_only_reencoded_pixels_without_original_metadata(): void
    {
        if (!function_exists('imagejpeg') || !function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('PHP GD is required for JPEG re-encoding.');
        }
        $this->contest();
        $this->clock('2026-11-19 18:00:00');
        $this->asCustomer(10);
        $image = imagecreatetruecolor(8, 8);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();
        imagedestroy($image);
        $marker = 'SPAD_PRIVATE_IMAGE_METADATA_FIXTURE';
        $source = substr($jpeg, 0, 2) . chr(255) . chr(254) . pack('n', strlen($marker) + 2) . $marker . substr($jpeg, 2);
        $response = $this->post(self::BASE . '/fall/entries', [
            'title' => 'اتاق روشن', 'description' => 'تصویر آزمایشی اتاق برای آزمون ثبت اثر.',
            'province' => 'فارس', 'city' => 'شیراز', 'ai_disclosed' => '0',
            'ownership_confirmed' => '1', 'publication_confirmed' => '1',
            'photos' => [UploadedFile::fake()->createWithContent('source.jpg', $source)],
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.status', 'pending');
        $photo = DB::table(self::PHOTOS)->where('entry_id', $response->json('data.id'))->first();
        $bytes = Storage::disk('local')->get($photo->path);
        $this->assertStringNotContainsString($marker, $bytes);
        $this->assertSame('image/jpeg', getimagesizefromstring($bytes)['mime']);
    }

    public function test_admin_cannot_publish_a_revision_that_has_changed_since_review(): void
    {
        $contest = $this->contest();
        $id = $this->entry($contest, 10, 'pending');
        DB::table(self::PHOTOS)->insert(['entry_id' => $id, 'position' => 0, 'path' => 'private/spad-home-league/example.jpg']);
        $this->clock('2026-11-19 18:00:00');
        $this->asCustomer(10);
        $this->postJson(self::BASE . '/fall/entries/' . $id, [
            'title' => 'اثر ویرایش‌شده', 'description' => 'محتوا پس از مشاهدهٔ نسخهٔ پیشین ویرایش شد.',
            'province' => 'فارس', 'ai_disclosed' => true,
            'ownership_confirmed' => true, 'publication_confirmed' => true,
        ])->assertOk();

        $this->withoutMiddleware(SpadHomeServiceAdministrator::class);
        $this->actingAs((new Admin())->forceFill(['id' => 1]), 'admin');
        $review = '/api/admin/home-league/entries/' . $id . '/moderate';
        $this->postJson($review, ['status' => 'approved', 'revision' => 1])->assertStatus(409);
        $this->getJson(self::BASE . '/fall/entries/' . $id)->assertNotFound();
        $this->postJson($review, ['status' => 'approved', 'revision' => 2])->assertOk();
        $this->getJson(self::BASE . '/fall/entries/' . $id)->assertOk()->assertJsonPath('data.entry.title', 'اثر ویرایش‌شده');
    }

    private function contest(): int
    {
        return DB::table(self::CONTESTS)->insertGetId([
            'slug' => 'fall', 'title' => 'لیگ خانه', 'theme' => 'خانه ایرانی',
            'description' => 'مسابقه آزمایشی', 'rules' => 'قانون نمونه', 'published' => true,
            'submission_starts_at' => '2026-11-18 12:00:00', 'submission_ends_at' => '2026-11-20 12:00:00',
            'voting_starts_at' => '2026-11-20 12:00:00', 'voting_ends_at' => '2026-11-21 12:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function entry(int $contest, int $owner, string $status): int
    {
        return DB::table(self::ENTRIES)->insertGetId([
            'contest_id' => $contest, 'user_id' => $owner,
            'title' => 'اثر آزمایشی', 'description' => 'شرح اثر برای آزمون لیگ خانه.',
            'province' => 'فارس', 'city' => 'شیراز', 'status' => $status, 'ai_disclosed' => false,
            'attested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function asCustomer(int $id): void
    {
        Sanctum::actingAs((new User())->forceFill(['id' => $id]));
    }

    private function clock(string $at): void
    {
        Carbon::setTestNow(Carbon::parse($at, self::CLOCK));
    }
}
