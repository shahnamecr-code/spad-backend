<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomePlanner extends Migration
{
    public function up()
    {
        Schema::create('spad_home_planner_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('owner_id')->index();
            $table->timestamps();
        });
        Schema::create('spad_home_planner_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('spad_home_planner_groups')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('role', 10);
            $table->timestamp('removed_at')->nullable();
            $table->unique(['group_id', 'user_id']);
            $table->timestamps();
        });
        Schema::create('spad_home_planner_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('spad_home_planner_groups')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->char('token_hash', 64)->unique();
            $table->string('role', 10);
            $table->timestamp('expires_at')->index();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('spad_home_planner_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('spad_home_planner_groups')->cascadeOnDelete();
            $table->unsignedBigInteger('assignee_id')->nullable()->index();
            $table->unsignedBigInteger('created_by');
            $table->string('title', 120);
            $table->text('notes')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('recurrence', 10);
            $table->unsignedSmallInteger('interval_count')->default(1);
            $table->string('interval_unit', 8)->nullable();
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->index(['group_id', 'starts_on', 'archived_at']);
            $table->timestamps();
        });
        Schema::create('spad_home_planner_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('spad_home_planner_tasks')->cascadeOnDelete();
            $table->unsignedBigInteger('group_id')->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->date('due_on');
            $table->string('title', 120);
            $table->unsignedSmallInteger('points_awarded');
            $table->timestamp('completed_at');
            $table->unique(['task_id', 'due_on']);
            $table->index(['group_id', 'due_on']);
        });
    }

    public function down()
    {
        foreach (['occurrences', 'tasks', 'invites', 'members', 'groups'] as $table) {
            Schema::dropIfExists('spad_home_planner_'.$table);
        }
    }
}
