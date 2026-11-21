<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SpadHomePlanner as Planner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHomePlannerTest extends TestCase
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
        require_once database_path('migrations/2026_11_20_160001_create_spad_home_service_operations.php');
        require_once database_path('migrations/2026_11_21_190000_add_spad_household_services_settings.php');
        require_once database_path('migrations/2026_11_21_130002_create_spad_home_planner.php');
        (new \CreateSpadHomeServiceOperations())->up();
        (new \AddSpadHouseholdServicesSettings())->up();
        (new \CreateSpadHomePlanner())->up();
    }

    public function test_monthly_recurrence_clips_without_changing_the_anchor()
    {
        $task = (object) ['starts_on' => '2026-09-22', 'ends_on' => null, 'recurrence' => 'monthly'];
        $this->assertTrue(Planner::matches($task, Planner::day('2026-10-22')));
        $this->assertFalse(Planner::matches($task, Planner::day('2026-10-21')));
        $this->assertTrue(Planner::matches($task, Planner::day('2027-04-20')));
        $task->starts_on = '2026-01-31';
        $task->recurrence = 'custom';
        $task->interval_unit = 'week';
        $task->interval_count = 2;
        $this->assertFalse(Planner::matches($task, Planner::day('2026-02-07')));
        $this->assertTrue(Planner::matches($task, Planner::day('2026-02-14')));
    }

    public function test_private_group_invite_member_assignment_and_unique_credit()
    {
        $owner = User::forceCreate(['first_name' => 'سارا', 'status' => User::APPROVED]);
        $member = User::forceCreate(['first_name' => 'آرمان', 'status' => User::APPROVED]);
        $outsider = User::forceCreate(['first_name' => 'علی', 'status' => User::APPROVED]);
        $api = '/api/home-planner';
        Sanctum::actingAs($owner);
        $group = $this->postJson($api.'/groups', ['name' => 'خانواده'])->assertCreated()->json('data.id');
        $root = $api.'/groups/'.$group;
        $invite = $this->postJson($root.'/invitations', ['role' => 'member'])->assertCreated()->json('data.token');
        $this->assertDatabaseHas(Planner::INVITES, ['token_hash' => hash('sha256', $invite)]);
        Sanctum::actingAs($member);
        $this->getJson($root)->assertNotFound();
        $this->postJson($api.'/invitations/accept', ['token' => $invite])->assertOk();
        $this->postJson($api.'/invitations/accept', ['token' => $invite])->assertOk();
        $membership = $this->getJson($root)->assertOk()->json('data.group.member_id');
        $today = now(Planner::ZONE)->toDateString();
        $body = ['title' => 'آبیاری', 'assignee_id' => $membership, 'starts_on' => $today, 'recurrence' => 'daily', 'points' => 5];
        $this->postJson($root.'/tasks', $body)->assertForbidden();
        Sanctum::actingAs($outsider);
        $this->postJson($api.'/invitations/accept', ['token' => $invite])->assertNotFound();
        Sanctum::actingAs($owner);
        $id = $this->postJson($root.'/tasks', $body)->assertCreated()->json('data.id');
        $completion = $root.'/tasks/'.$id.'/occurrences/'.$today.'/completion';
        $this->putJson($completion)->assertForbidden();
        Sanctum::actingAs($member);
        $this->putJson($completion)->assertOk();
        $this->putJson($completion)->assertOk();
        $this->assertSame(1, DB::table(Planner::OCCURRENCES)->count());
        $this->getJson($root)->assertJsonPath('data.progress.points', 5);
    }
}
