<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadNeighbors extends Migration
{
    public function up()
    {
        Schema::create('spad_neighbor_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedBigInteger('active_region_id')->nullable();
            $table->boolean('notify_replies')->default(false);
            $table->boolean('notify_followed')->default(false);
            $table->timestamps();
        });
        Schema::create('spad_neighbor_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('region_id')->index();
            $table->unique(['user_id', 'region_id']);
            $table->timestamps();
        });
        Schema::create('spad_neighbor_residencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('region_id');
            $table->string('status', 16)->default('pending')->index();
            $table->unique(['user_id', 'region_id']);
            $table->timestamps();
        });
        Schema::create('spad_neighbor_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id')->index();
            $table->unsignedBigInteger('region_id');
            $table->string('category', 32);
            $table->string('title', 120);
            $table->text('body');
            $table->string('status', 16)->default('pending')->index();
            $table->string('lost_status', 16)->nullable();
            $table->timestamp('event_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->index(['region_id', 'status', 'id']);
            $table->timestamps();
        });
        Schema::create('spad_neighbor_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->index();
            $table->string('path');
            $table->timestamps();
        });
        Schema::create('spad_neighbor_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->index();
            $table->unsignedBigInteger('author_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->text('body');
            $table->string('status', 16)->default('pending')->index();
            $table->timestamps();
        });
        Schema::create('spad_neighbor_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('kind', 16);
            $table->unique(['post_id', 'user_id', 'kind']);
            $table->index(['post_id', 'kind']);
            $table->timestamps();
        });
        foreach (['reports', 'appeals'] as $name) {
            Schema::create('spad_neighbor_'.$name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('target_type', 16);
                $table->unsignedBigInteger('target_id');
                $table->string('status', 16)->default('open')->index();
                $table->string('reason', 500);
                $table->unique(['user_id', 'target_type', 'target_id']);
                $table->timestamps();
            });
        }
        Schema::create('spad_neighbor_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->string('event_key', 72);
            $table->string('title', 120);
            $table->timestamp('read_at')->nullable();
            $table->unique(['user_id', 'event_key']);
            $table->timestamps();
        });
        Schema::create('spad_neighbor_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('resource', 24);
            $table->unsignedBigInteger('record_id');
            $table->string('from_status', 16);
            $table->string('to_status', 16);
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        foreach (['audits', 'notices', 'appeals', 'reports', 'interactions', 'comments', 'media', 'posts', 'residencies', 'follows', 'preferences'] as $table) {
            Schema::dropIfExists('spad_neighbor_'.$table);
        }
    }
}
