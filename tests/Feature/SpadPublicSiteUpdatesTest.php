<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SpadAiAssistantSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpadPublicSiteUpdatesTest extends TestCase
{
    public function test_daily_visitors_are_deduplicated_and_user_details_require_a_super_admin(): void
    {
        require_once database_path('migrations/2026_11_28_120000_create_spad_public_site_visitors_table.php');
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        (new \CreateSpadPublicSiteVisitorsTable())->up();
        (new \CreateUsersTable())->up();
        Schema::table('users', static function (Blueprint $table) { $table->softDeletes(); });
        Schema::create('roles', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', static function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('model_id');
            $table->string('model_type');
        });
        Schema::create('mortgage_requests', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        $browserId = str_repeat('a', 32);
        $this->postJson('/api/site-usage/visit', ['browser_id' => $browserId])->assertNoContent();
        $this->postJson('/api/site-usage/visit', ['browser_id' => $browserId])->assertNoContent();
        $this->assertDatabaseCount('spad_public_site_visitors', 1);
        $this->assertNotSame($browserId, DB::table('spad_public_site_visitors')->value('browser_hash'));

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'کاربر', 'last_name' => 'آزمایشی', 'password' => 'fixture-only',
            'mobile' => '09120000000', 'created_at' => now('Asia/Tehran')->startOfDay(),
        ]);
        DB::table('mortgage_requests')->insert(['user_id' => $userId, 'created_at' => now('Asia/Tehran')]);
        $this->getJson('/api/admin/site-usage/users')->assertUnauthorized();
        $admin = new Admin();
        $admin->id = 17;
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/site-usage/users')->assertForbidden();

        DB::table('roles')->insert(['id' => 1, 'name' => 'Super Admin', 'guard_name' => 'admin']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => 17, 'model_type' => Admin::class]);
        $admin->unsetRelation('roles');

        $report = $this->getJson('/api/admin/site-usage/users')->assertOk()
            ->assertJsonPath('data.visitors', 1)
            ->assertJsonPath('data.new_accounts', 1)
            ->assertJsonPath('data.users.0.id', $userId)
            ->assertJsonPath('data.users.0.mobile', '09120000000')
            ->assertJsonPath('data.users.0.actions.0.label', 'ثبت درخواست وام مسکن');
        $this->assertArrayNotHasKey('password', $report->json('data.users.0'));
    }

    public function test_chat_and_private_tour_use_the_same_encrypted_key_and_keep_projects_editable(): void
    {
        require_once database_path('migrations/2026_11_03_001000_create_spad_ai_assistant_settings.php');
        require_once database_path('migrations/2026_11_01_000000_create_spad_online_renovation.php');
        (new \CreateSpadAiAssistantSettings())->up();
        (new \CreateSpadOnlineRenovation())->up();
        Schema::table('spad_renovation_projects', static function (Blueprint $table) {
            $table->json('design_layers')->nullable();
        });
        Schema::create('spad_renovation_assets', static function (Blueprint $table) {
            $table->string('code');
            $table->string('title');
            $table->string('category');
            $table->string('color_hex');
            $table->string('reference_path')->nullable();
            $table->boolean('enabled');
        });
        DB::table('spad_renovation_assets')->insert([
            'code' => 'catalog-sink', 'title' => 'سینک آزمایشی', 'category' => 'sink', 'color_hex' => '#ffffff', 'enabled' => true,
        ]);
        Storage::fake('local');
        $project = DB::table('spad_renovation_projects')->insertGetId([
            'title' => 'پروژه آزمایشی', 'user_id' => 44, 'source_type' => 'user_upload',
        ]);
        $mediaIds = [];
        foreach ([[450, 300], [1024, 512]] as $index => [$width, $height]) {
            $image = imagecreatetruecolor($width, $height);
            ob_start();
            imagejpeg($image);
            $jpeg = ob_get_clean();
            imagedestroy($image);
            $path = 'private/renovation/test-' . $index . '.jpg';
            Storage::disk('local')->put($path, $jpeg);
            $mediaIds[] = DB::table('spad_renovation_media')->insertGetId([
                'project_id' => $project, 'user_id' => 44, 'kind' => 'source',
                'path' => $path, 'mime' => 'image/jpeg', 'bytes' => strlen($jpeg),
            ]);
        }
        $user = new User();
        $user->id = 44;
        Sanctum::actingAs($user);
        $tourPath = '/api/online-renovation/' . $project . '/ai-tour';
        $this->getJson('/api/online-renovation/ai-status')->assertJsonPath('data.tour_available', false);
        SpadAiAssistantSetting::query()->create([
            'enabled' => true, 'api_key_encrypted' => Crypt::encryptString('mock-spad-openai-test-key'),
        ]);

        Http::fake([
            'api.openai.com/v1/images/edits' => static function ($request) use ($jpeg) {
                self::assertTrue($request->hasHeader('Authorization', 'Bearer mock-spad-openai-test-key'));
                self::assertTrue($request->hasFile('image[]', null, 'room.png'));
                self::assertTrue($request->hasFile('mask', null, 'mask.png'));
                return Http::response(['data' => [['b64_json' => base64_encode($jpeg)]]]);
            },
            'api.openai.com/v1/responses' => static function ($request) {
                self::assertTrue($request->hasHeader('Authorization', 'Bearer mock-spad-openai-test-key'));
                self::assertFalse($request['store']);
                $answer = 'پاسخ آزمایشی دستیار';
                if (isset($request['text'])) {
                    $prompt = $request['input'][0]['content'][0]['text'];
                    $answer = strpos($prompt, 'Locate the visible existing fixture') === 0
                        ? json_encode(['found' => true, 'x' => 10, 'y' => 20, 'w' => 25, 'h' => 15])
                        : json_encode(['scenes' => [['image' => 2, 'title' => 'پذیرایی'], ['image' => 1, 'title' => 'ورودی']]]);
                }
                return Http::response(['output' => [[
                    'type' => 'message', 'content' => [['type' => 'output_text', 'text' => $answer]],
                ]]]);
            },
        ]);

        $this->postJson($tourPath, ['consent' => false])->assertStatus(422);
        $this->postJson($tourPath, ['consent' => true])->assertOk()
            ->assertJsonPath('data.0.media_id', $mediaIds[1])
            ->assertJsonPath('data.0.kind', 'panorama')
            ->assertJsonPath('data.0.next_media_id', $mediaIds[0])
            ->assertJsonPath('data.1.kind', 'photo')
            ->assertJsonPath('data.1.next_media_id', null);

        $this->postJson('/api/ai-assistant/chat', ['message' => 'شرایط وام چیست؟'])->assertOk()
            ->assertJsonPath('answer', 'پاسخ آزمایشی دستیار');
        $this->postJson('/api/online-renovation/' . $project . '/ai-placement', [
            'media_id' => $mediaIds[0], 'asset_code' => 'catalog-sink', 'frame_ratio' => 1.5, 'consent' => true,
        ])->assertOk()->assertJsonPath('data.x', 10)->assertJsonPath('data.h', 15);
        $user->id = 45;
        Sanctum::actingAs($user);
        $this->postJson($tourPath, ['consent' => true])->assertNotFound();
        // Give the owner a fresh request budget for image editing.
        app('cache')->flush();
        $user->id = 44;
        Sanctum::actingAs($user);
        DB::table('spad_renovation_projects')->where('id', $project)->update([
            'design_layers' => json_encode([[
                'asset_code' => 'catalog-sink', 'scene_key' => 'image:' . $mediaIds[0],
                'x' => 10, 'y' => 20, 'w' => 25, 'h' => 15,
            ]]),
        ]);
        $render = $this->postJson('/api/online-renovation/' . $project . '/ai-preview', [
            'media_id' => $mediaIds[0], 'layer_index' => 0, 'frame_ratio' => 1.5, 'consent' => true,
        ])->assertCreated()->assertJsonPath('data.asset_code', 'catalog-sink');
        $this->assertDatabaseHas('spad_renovation_media', [
            'id' => $render->json('data.id'), 'project_id' => $project, 'user_id' => 44, 'kind' => 'output',
        ]);
        Http::assertSentCount(4);
        $this->assertDatabaseHas('spad_renovation_projects', ['id' => $project, 'status' => 'draft']);
    }
}
