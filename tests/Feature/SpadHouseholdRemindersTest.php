<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SpadHouseholdReminders;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpadHouseholdRemindersTest extends TestCase
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
        Schema::create('spad_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('link');
            $table->string('audience');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        require_once database_path('migrations/2026_11_21_190001_create_spad_household_reminders.php');
        (new \CreateSpadHouseholdReminders())->up();
    }

    public function test_reminders_belong_to_the_account_and_deliver_only_once()
    {
        $route = '/api/household/reminders';
        $this->getJson($route)->assertUnauthorized();
        $owner = User::forceCreate(['status' => User::APPROVED]);
        $other = User::forceCreate(['status' => User::APPROVED]);
        Sanctum::actingAs($owner);
        $payload = [
            'service' => 'book', 'title' => 'یادداشت خصوصی', 'due_at' => now('UTC')->addMinute()->format('Y-m-d\TH:i:s\Z'),
            'request_key' => 'same-book-request-233043',
        ];
        $id = $this->postJson($route, $payload)->assertCreated()->json('data.id');
        $this->postJson($route, $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson($route, array_replace($payload, ['title' => 'دوم']))->assertConflict();
        Sanctum::actingAs($other);
        $this->getJson($route)->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson($route.'/'.$id)->assertNoContent();
        Sanctum::actingAs($owner);
        $this->getJson($route)->assertOk()->assertJsonPath('data.0.id', $id);
        SpadHouseholdReminders::deliverDue();
        $this->assertSame(0, DB::table('spad_app_notifications')->count());
        Carbon::setTestNow(now('UTC')->addMinutes(2));
        try {
            SpadHouseholdReminders::deliverDue();
            SpadHouseholdReminders::deliverDue();
            $this->assertSame(1, DB::table('spad_app_notifications')->count());
            $this->assertDatabaseHas('spad_app_notifications', ['link' => '/home-book', 'audience' => 'user', 'user_id' => $owner->id]);
            $this->assertStringNotContainsString($payload['title'], DB::table('spad_app_notifications')->value('body'));
            $this->getJson($route)->assertOk()->assertJsonPath('data.0.status', 'sent');
        } finally {
            Carbon::setTestNow();
        }
    }
}
