<?php

namespace Tests\Feature;

use App\Jobs\AssessSpadHomeDoctorCase;
use App\Models\SpadAiAssistantSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ProviderRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomeDoctorTest extends TestCase
{
    private const ROOT = '/api/home-doctor';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $migrations = [
            '2026_11_03_001000_create_spad_ai_assistant_settings.php' => \CreateSpadAiAssistantSettings::class,
            '2026_11_20_160000_create_spad_home_doctor.php' => \CreateSpadHomeDoctor::class,
            '2026_11_20_160001_create_spad_home_service_operations.php' => \CreateSpadHomeServiceOperations::class,
            '2026_11_20_160002_create_spad_home_doctor_category_overrides.php' => \CreateSpadHomeDoctorCategoryOverrides::class,
        ];
        foreach ($migrations as $file => $migration) {
            require_once database_path('migrations/' . $file);
            (new $migration())->up();
        }
        Storage::fake('local');
        config(['queue.default' => 'sync']);
    }

    private function login(): User
    {
        $user = User::forceCreate(['mobile' => '09120000000']);
        Sanctum::actingAs($user);
        return $user;
    }

    private function draft(string $description = 'روی دیوار آشپزخانه لکهٔ رطوبت دیده می‌شود.'): string
    {
        $data = $this->postJson(self::ROOT . '/cases', [
            'category' => 'moisture',
            'description' => $description,
        ])->assertCreated()->json('data');

        return self::ROOT . '/cases/' . $data['id'];
    }

    public function test_public_catalog_and_owner_isolation_for_cases()
    {
        $this->getJson(self::ROOT . '/categories')->assertOk()->assertJsonCount(12, 'data');
        $this->getJson(self::ROOT . '/cases')->assertUnauthorized();

        $owner = $this->login();
        $case = $this->draft();
        $this->getJson($case)->assertOk()->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.questions')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson(self::ROOT . '/cases')->assertOk()->assertJsonCount(1, 'data');

        $this->login();
        $this->getJson($case)->assertNotFound();
        $this->deleteJson($case)->assertNotFound();
        $this->postJson($case . '/analysis', ['consent' => true])->assertNotFound();
        $this->getJson(self::ROOT . '/cases')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($owner);
        $owner->withAccessToken(new PersonalAccessToken(['name' => 'partner-token']));
        $this->getJson($case)->assertForbidden();
        Sanctum::actingAs($owner);
        $this->deleteJson($case)->assertNoContent();
        $this->getJson($case)->assertNotFound();
    }

    public function test_safety_rules_precede_the_provider_for_descriptions_and_answers()
    {
        $this->login();
        Http::fake();

        foreach ([
            'بوی گاز شدید از آشپزخانه می‌آید.',
            'از پریز اتاق جرقه بیرون می‌آید.',
            'از پشت در دود غلیظ می‌آید.',
            'سقف خانه شکم داده و احتمال ریزش هست.',
        ] as $symptom) {
            $case = $this->draft($symptom);
            $this->getJson($case)->assertOk()->assertJsonPath('data.status', 'referral')
                ->assertJsonPath('data.result.source', 'safety_rule')
                ->assertJsonPath('data.result.urgency', 'urgent');
        }

        $case = $this->draft();
        $this->postJson($case . '/analysis', ['consent' => true, 'answers' => ['location' => 'روی پریز سیم لخت دیده می‌شود.']])
            ->assertOk()->assertJsonPath('data.status', 'referral')->assertJsonPath('data.result.source', 'safety_rule');
        Http::assertNothingSent();
    }

    public function test_consent_and_configured_provider_produce_private_structured_results()
    {
        $this->login();
        config(['services.openai.api_key' => 'fake-test-credential']);
        SpadAiAssistantSetting::create(['enabled' => true, 'model' => 'gpt-4.1-mini']);

        $provider = [
            'summary' => 'رطوبت موضعی مشاهده شده است.',
            'urgency' => 'soon',
            'confidence' => 'low',
            'observations' => ['لکه در محدودهٔ آشپزخانه است.'],
            'possible_causes' => ['امکان نشت آب یا تراکم بخار وجود دارد.'],
            'actions' => ['observe'],
            'specialist' => 'plumber',
            'risk' => 'none',
        ];
        Http::fake(['api.openai.com/v1/responses' => Http::response([
            'status' => 'completed',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($provider)]]]],
        ])]);

        $case = $this->draft('روی دیوار لکهٔ آب دیده می‌شود. شماره تماس 09123456789');
        $this->postJson($case . '/analysis', ['answers' => []])->assertStatus(422);
        Http::assertNothingSent();
        $this->postJson($case . '/analysis', ['consent' => true, 'answers' => ['symptom' => 'بعد از بارندگی بیشتر می‌شود.']])
            ->assertOk()->assertJsonPath('data.status', 'analyzed')->assertJsonPath('data.result.source', 'ai')
            ->assertJsonPath('data.result.confidence', 'low')->assertJsonCount(2, 'data.result.next_steps');
        $this->postJson($case . '/analysis', ['consent' => true])->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(function (ProviderRequest $request) {
            $data = $request->data();
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $data['store'] === false
                && $data['model'] === 'gpt-4.1-mini'
                && $data['text']['format']['strict'] === true
                && strpos($data['input'][0]['content'][0]['text'], '09123456789') === false
                && strpos($data['input'][0]['content'][0]['text'], 'user_id') === false;
        });

        $this->postJson($case . '/follow-ups', ['question' => 'آیا این لکه ممکن است از بارندگی باشد؟', 'consent' => true])
            ->assertCreated()->assertJsonPath('data.follow_ups.0.status', 'analyzed');
        $this->postJson($case . '/follow-ups', ['question' => 'حالا از پریز جرقه زده است.', 'consent' => true])
            ->assertCreated()->assertJsonPath('data.follow_ups.1.status', 'referral');
        $this->postJson($case . '/follow-ups', ['question' => 'حالا چه کار کنم؟', 'consent' => true])->assertStatus(409);
        Http::assertSentCount(2);
        $this->putJson($case . '/feedback', ['rating' => 4, 'comment' => 'ثبت بازخورد'])
            ->assertOk()->assertJsonPath('data.feedback.rating', 4);
    }

    public function test_unavailable_provider_and_queued_job_have_truthful_status()
    {
        $this->login();
        Http::fake();
        $case = $this->draft();
        $this->postJson($case . '/analysis', ['consent' => true])
            ->assertOk()->assertJsonPath('data.status', 'unavailable')->assertJsonPath('data.result', null);
        Http::assertNothingSent();

        Queue::fake();
        $this->postJson($case . '/analysis', ['consent' => true])
            ->assertOk()->assertJsonPath('data.status', 'queued')->assertJsonPath('data.result', null);
        $this->postJson($case . '/analysis', ['consent' => true])->assertOk();
        Queue::assertPushed(AssessSpadHomeDoctorCase::class, 1);
        DB::table('spad_home_doctor_cases')->update(['updated_at' => now()->subMinutes(21)]);
        $this->getJson($case)->assertOk()->assertJsonPath('data.status', 'unavailable');
    }

    public function test_photo_bytes_are_reencoded_and_are_accessible_only_by_the_owner()
    {
        $owner = $this->login();
        $case = $this->draft();
        $image = UploadedFile::fake()->image('original.jpg', 70, 50);
        $marker = 'private-original-photo-metadata';
        $bytes = file_get_contents($image->getRealPath());
        file_put_contents($image->getRealPath(), substr($bytes, 0, 2) . "\xff\xfe" .
            pack('n', strlen($marker) + 2) . $marker . substr($bytes, 2));
        $response = $this->post($case . '/media', ['image' => $image], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.media.0.width', 70);
        $this->assertArrayNotHasKey('path', $response->json('data.media.0'));
        $media = $this->getJson($case)->json('data.media.0.id');
        $private = $case . '/media/' . $media;
        $this->get($private)->assertOk()->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'no-store, private');
        $file = DB::table('spad_home_doctor_media')->where('id', $media)->value('path');
        Storage::disk('local')->assertExists($file);
        $this->assertStringNotContainsString($marker, Storage::disk('local')->get($file));

        $this->login();
        $this->getJson($private)->assertNotFound();
        $this->deleteJson($private)->assertNotFound();

        Sanctum::actingAs($owner);
        $this->deleteJson($case)->assertNoContent();
        Storage::disk('local')->assertMissing($file);
        $this->getJson($private)->assertNotFound();
    }
}
