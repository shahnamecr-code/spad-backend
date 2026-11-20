<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpadNeighborsTest extends TestCase
{
    private const ROOT = '/api/neighbors';
    private const ADMIN = '/api/admin/neighbors';

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('province_id');
            $table->timestamps();
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('city_id');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('phone_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        require_once database_path('migrations/2026_11_20_160001_create_spad_home_service_operations.php');
        require_once database_path('migrations/2026_11_20_180001_create_spad_neighbors.php');
        (new \CreateSpadHomeServiceOperations())->up();
        (new \CreateSpadNeighbors())->up();

        DB::table('cities')->insert([
            ['id' => 1, 'name' => 'شیراز', 'province_id' => 1],
            ['id' => 2, 'name' => 'مرودشت', 'province_id' => 1],
        ]);
        DB::table('regions')->insert([
            ['id' => 11, 'city_id' => 1, 'name' => 'قصرالدشت'],
            ['id' => 12, 'city_id' => 1, 'name' => 'معالی‌آباد'],
            ['id' => 21, 'city_id' => 2, 'name' => 'محلهٔ دیگر'],
        ]);
        Storage::fake('local');
    }

    private function member(string $label, bool $verified = true): array
    {
        $user = User::forceCreate([
            'first_name' => $label, 'last_name' => 'نام خصوصی', 'mobile' => '09120001234',
            'email' => 'private@example.test', 'status' => User::APPROVED,
            'phone_verified_at' => $verified ? now() : null,
        ]);
        return [$user->id, ['Authorization' => 'Bearer '.$user->createToken('neighbors-test')->plainTextToken]];
    }

    private function superAdmin(bool $approved = true, bool $super = true): Admin
    {
        $admin = Admin::forceCreate(['username' => 'op', 'status' => $approved ? Admin::APPROVED : Admin::NOT_APPROVED]);
        if ($super) {
            $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id')
                ?? DB::table('roles')->insertGetId(['name' => 'Super Admin', 'guard_name' => 'admin']);
            DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => Admin::class, 'model_id' => $admin->id]);
        }
        return $admin;
    }

    private function callAs(string $method, string $path, array $payload = [], array $heads = [])
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        return $this->json($method, self::ROOT.$path, $payload, $heads);
    }

    private function moderate(Admin $admin, string $group, int $id, string $status)
    {
        return $this->actingAs($admin, 'admin')->patchJson(self::ADMIN.'/'.$group.'/'.$id, [
            'status' => $status, 'note' => 'بررسی توسط مدیر',
        ]);
    }

    private function draft(array $owner, array $changes = [])
    {
        return $this->callAs('POST', '/posts', $changes + [
            'title' => 'درخت‌های محله', 'body' => 'برای رسیدگی به درختان کوچه همفکری کنیم.',
            'category' => 'questions', 'region_id' => 11,
        ], $owner);
    }

    public function test_shiraz_only_content_privacy_moderation_and_ownership()
    {
        [, $writer] = $this->member('همسایه');
        [, $other] = $this->member('عضو');
        [, $unverified] = $this->member('تازه‌وارد', false);
        $admin = $this->superAdmin();

        $this->callAs('GET', '/regions')->assertOk()->assertJsonCount(2, 'data.regions')->assertJsonCount(8, 'data.categories');
        $this->callAs('GET', '/posts?region_id=21')->assertStatus(422);
        $this->draft([])->assertStatus(401);
        $this->draft($unverified)->assertStatus(403);
        $this->draft($writer, ['region_id' => 21])->assertStatus(422);
        $this->draft($writer, ['body' => 'شمارهٔ من ۰۹۱۲ ۰۰۰ ۱۲۳۴ است.'])->assertStatus(422);
        $id = $this->draft($writer, ['client_key' => 'same-action-retry'])->assertOk()
            ->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->draft($writer, ['client_key' => 'same-action-retry'])->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSame(1, DB::table('spad_neighbor_posts')->count());
        $this->callAs('GET', '/posts/'.$id)->assertStatus(404);
        $this->callAs('PATCH', '/posts/'.$id, ['region_id' => 11, 'category' => 'questions', 'title' => 'دست‌کاری', 'body' => 'متن'], $other)->assertStatus(404);
        $this->callAs('GET', '/my-posts', [], $other)->assertOk()->assertJsonCount(0, 'data');
        $this->callAs('GET', '/my-posts', [], $writer)->assertOk()->assertJsonPath('data.0.own', true);
        $this->moderate($this->superAdmin(true, false), 'posts', $id, 'published')->assertStatus(403);
        $this->moderate($this->superAdmin(false), 'posts', $id, 'published')->assertStatus(403);
        $this->moderate($admin, 'posts', $id, 'published')->assertOk();
        $this->callAs('GET', '/posts?region_id=11&category=questions&q='.rawurlencode('درخت'))->assertJsonCount(1, 'data');
        $public = $this->callAs('GET', '/posts/'.$id)->assertOk()
            ->assertJsonPath('data.author', 'همسایه')->assertJsonPath('data.own', false)->assertJsonPath('data.resident_verified', false)->json('data');
        foreach (['author_id', 'mobile', 'email', 'last_name', 'address', 'phone_verified_at'] as $key) {
            $this->assertArrayNotHasKey($key, $public);
        }
        $this->callAs('GET', '/posts/'.$id, [], $writer)->assertJsonPath('data.own', true);
        $this->callAs('DELETE', '/posts/'.$id, [], $other)->assertStatus(404);
        $this->callAs('DELETE', '/posts/'.$id, [], $writer)->assertOk();
        $this->callAs('GET', '/posts/'.$id)->assertStatus(404);
    }

    public function test_follow_residency_notifications_discussion_and_interactions()
    {
        [$writerId, $writer] = $this->member('نویسنده');
        [$otherId, $other] = $this->member('همسایه');
        [, $guestMember] = $this->member('بازدیدکننده');
        $admin = $this->superAdmin();

        $this->callAs('PATCH', '/preferences', ['active_region_id' => 11, 'notify_followed' => true], $other)->assertOk()->assertJsonPath('data.active_region_id', 11);
        $this->callAs('PUT', '/follows/11', [], $other)->assertOk()->assertJsonPath('data.followed_regions.0', 11);
        $this->callAs('PUT', '/follows/11', [], $other)->assertOk()->assertJsonCount(1, 'data.followed_regions');
        $this->callAs('PATCH', '/preferences', ['notify_replies' => true], $writer)->assertOk();
        $this->callAs('POST', '/residency/11', ['consent' => true], $writer)->assertOk()->assertJsonPath('data.residencies.0.status', 'pending');
        $residency = DB::table('spad_neighbor_residencies')->where('user_id', $writerId)->value('id');
        $this->moderate($admin, 'residencies', $residency, 'verified')->assertOk();

        $id = $this->draft($writer)->assertOk()->json('data.id');
        $this->moderate($admin, 'posts', $id, 'published')->assertOk();
        $this->callAs('GET', '/posts/'.$id)->assertJsonPath('data.resident_verified', true);
        $notice = $this->callAs('GET', '/notifications', [], $other)->assertJsonCount(1, 'data')->json('data.0.id');
        $this->callAs('GET', '/notifications', [], $guestMember)->assertJsonCount(0, 'data');
        $this->callAs('PATCH', '/notifications/'.$notice, [], $writer)->assertStatus(404);
        $this->callAs('PATCH', '/notifications/'.$notice, [], $other)->assertOk();
        $this->callAs('GET', '/me', [], $other)->assertJsonPath('data.unread', 0);

        $this->callAs('PUT', '/posts/'.$id.'/interactions/like', [], $other)->assertOk();
        $this->callAs('PUT', '/posts/'.$id.'/interactions/like', [], $other)->assertOk();
        $this->callAs('PUT', '/posts/'.$id.'/interactions/bookmark', [], $other)->assertOk();
        $this->callAs('GET', '/bookmarks', [], $other)->assertJsonPath('data.0.liked', true)->assertJsonPath('data.0.likes', 1);
        $this->callAs('GET', '/bookmarks', [], $writer)->assertJsonCount(0, 'data');
        $comment = $this->callAs('POST', '/posts/'.$id.'/comments', ['body' => 'با رسیدگی موافقم.'], $other)->assertOk()->json('data.id');
        $this->callAs('GET', '/posts/'.$id.'/comments')->assertJsonCount(0, 'data');
        $this->moderate($admin, 'comments', $comment, 'published')->assertOk();
        $this->callAs('GET', '/notifications', [], $writer)->assertJsonCount(1, 'data');
        $reply = $this->callAs('POST', '/posts/'.$id.'/comments', ['body' => 'ممنون از نظر شما.', 'parent_id' => $comment], $writer)->assertOk()->json('data.id');
        $this->moderate($admin, 'comments', $reply, 'published')->assertOk();
        $this->callAs('GET', '/posts/'.$id.'/comments')->assertJsonCount(2, 'data');
        $this->callAs('DELETE', '/comments/'.$comment, [], $writer)->assertStatus(404);
        $this->callAs('DELETE', '/comments/'.$comment, [], $other)->assertOk();
        $this->callAs('GET', '/posts/'.$id.'/comments')->assertJsonCount(0, 'data');
        $this->callAs('GET', '/posts/'.$id)->assertJsonPath('data.comments', 0);
        $this->assertDatabaseHas('spad_neighbor_notices', ['user_id' => $otherId, 'event_key' => 'published-'.$id]);
    }

    public function test_private_media_review_appeals_and_revocation()
    {
        [, $owner] = $this->member('نویسنده');
        [, $reporter] = $this->member('همسایه');
        $admin = $this->superAdmin();
        $id = $this->draft($owner, ['category' => 'lost-found', 'lost_status' => 'found'])->assertOk()->json('data.id');
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $media = $this->post(self::ROOT.'/posts/'.$id.'/media', [
            'image' => UploadedFile::fake()->image('front.jpg', 48, 32),
        ], $owner + ['Accept' => 'application/json'])->assertOk()->json('data.id');
        $this->callAs('GET', '/media/'.$media)->assertStatus(404);
        $this->callAs('GET', '/my-media/'.$media, [], $reporter)->assertStatus(404);
        $this->callAs('GET', '/my-media/'.$media, [], $owner)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->moderate($admin, 'posts', $id, 'published')->assertOk();
        $this->callAs('GET', '/media/'.$media)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->callAs('PATCH', '/posts/'.$id.'/resolve', [], $owner)->assertOk();
        $this->callAs('GET', '/posts?category=lost-found&lost_status=resolved')->assertJsonPath('data.0.id', $id);
        $reason = ['reason' => 'نیاز به بازبینی عمومی'];
        $this->callAs('POST', '/reports/post/'.$id, $reason, $reporter)->assertOk();
        $this->callAs('POST', '/reports/post/'.$id, $reason, $reporter)->assertOk();
        $this->assertSame(1, DB::table('spad_neighbor_reports')->count());
        $this->moderate($admin, 'posts', $id, 'hidden')->assertOk();
        $this->callAs('GET', '/media/'.$media)->assertStatus(404);
        $this->callAs('POST', '/appeals/post/'.$id, $reason, $reporter)->assertStatus(404);
        $this->callAs('POST', '/appeals/post/'.$id, $reason, $owner)->assertOk();
        $appeal = DB::table('spad_neighbor_appeals')->value('id');
        $this->moderate($admin, 'appeals', $appeal, 'accepted')->assertOk();
        $this->callAs('GET', '/posts/'.$id)->assertOk();
        $this->assertDatabaseHas('spad_neighbor_audits', ['resource' => 'appeals', 'record_id' => $appeal, 'to_status' => 'accepted']);

        $this->callAs('DELETE', '/posts/'.$id.'/media/'.$media, [], $owner)->assertOk();
        $this->callAs('GET', '/media/'.$media)->assertStatus(404);
        $this->callAs('GET', '/posts/'.$id)->assertStatus(404);
    }
}
