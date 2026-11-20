<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeLeagueTables extends Migration
{
    public function up(): void
    {
        Schema::create('spad_home_league_contests', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 180);
            $table->string('theme', 130);
            $table->text('description');
            $table->text('rules');
            $table->boolean('published')->default(false)->index();
            $table->dateTime('submission_starts_at');
            $table->dateTime('submission_ends_at');
            $table->dateTime('voting_starts_at');
            $table->dateTime('voting_ends_at');
            $table->dateTime('frozen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('spad_home_league_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('spad_home_league_contests')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title', 150);
            $table->text('description');
            $table->string('province', 85);
            $table->string('city', 85)->nullable();
            $table->boolean('ai_disclosed');
            $table->dateTime('attested_at');
            $table->unsignedInteger('revision')->default(1);
            $table->string('status', 20)->default('pending');
            $table->text('review_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['contest_id', 'status']);
        });

        Schema::create('spad_home_league_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('spad_home_league_entries')->onDelete('cascade');
            $table->string('path', 220);
            $table->unsignedTinyInteger('position');
            $table->unique(['entry_id', 'position']);
        });

        Schema::create('spad_home_league_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('spad_home_league_contests')->onDelete('cascade');
            $table->foreignId('entry_id')->constrained('spad_home_league_entries')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->date('vote_date');
            $table->boolean('valid')->default(true);
            $table->dateTime('invalidated_at')->nullable();
            $table->unsignedBigInteger('invalidated_by')->nullable();
            $table->text('invalidation_reason')->nullable();
            $table->timestamps();
            $table->unique(['contest_id', 'user_id', 'vote_date'], 'spad_home_league_one_vote_per_day');
            $table->index(['entry_id', 'valid']);
        });

        Schema::create('spad_home_league_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('spad_home_league_entries')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['entry_id', 'user_id']);
        });

        Schema::create('spad_home_league_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('spad_home_league_contests')->onDelete('cascade');
            $table->foreignId('entry_id')->constrained('spad_home_league_entries')->onDelete('cascade');
            $table->unsignedInteger('place');
            $table->unsignedInteger('vote_count');
            $table->string('badge', 12)->nullable();
            $table->unique(['contest_id', 'entry_id']);
            $table->index(['contest_id', 'place']);
        });

        Schema::create('spad_home_league_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('spad_home_league_contests')->onDelete('cascade');
            $table->unsignedBigInteger('admin_id');
            $table->string('action', 35);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('created_at');
            $table->index(['contest_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['audit', 'results', 'reports', 'votes', 'photos', 'entries', 'contests'] as $table) {
            Schema::dropIfExists('spad_home_league_' . $table);
        }
    }
}
