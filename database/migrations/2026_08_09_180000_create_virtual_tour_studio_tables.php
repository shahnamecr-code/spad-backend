<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVirtualTourStudioTables extends Migration
{
    public function up()
    {
        Schema::create('virtual_tours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id')->index();
            $table->string('title', 191);
            $table->string('slug', 191)->unique();
            $table->unsignedBigInteger('start_scene_id')->nullable()->index();
            $table->string('floorplan_path', 1000)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('advertisement_id')->references('id')->on('advertisements')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
        });

        Schema::create('virtual_tour_scenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('title', 191);
            $table->string('original_path', 1000);
            $table->string('ultra_path', 1000)->nullable();
            $table->string('high_path', 1000)->nullable();
            $table->string('medium_path', 1000)->nullable();
            $table->string('preview_path', 1000)->nullable();
            $table->string('thumbnail_path', 1000)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('initial_yaw', 8, 3)->default(0);
            $table->decimal('initial_pitch', 8, 3)->default(0);
            $table->decimal('initial_fov', 8, 3)->default(90);
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('process_status', ['pending', 'processing', 'ready', 'failed'])->default('pending')->index();
            $table->text('process_error')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('virtual_tours')->onDelete('cascade');
        });

        Schema::table('virtual_tours', function (Blueprint $table) {
            $table->foreign('start_scene_id')->references('id')->on('virtual_tour_scenes')->onDelete('set null');
        });

        Schema::create('virtual_tour_hotspots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scene_id')->index();
            $table->unsignedBigInteger('target_scene_id')->nullable()->index();
            $table->enum('type', ['navigation', 'information', 'feature', 'photo', 'video', 'link'])->index();
            $table->string('title', 191)->nullable();
            $table->text('content')->nullable();
            $table->decimal('yaw', 8, 3);
            $table->decimal('pitch', 8, 3);
            $table->string('icon', 100)->nullable();
            $table->string('media_path', 1000)->nullable();
            $table->string('link_url', 1000)->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();

            $table->foreign('scene_id')->references('id')->on('virtual_tour_scenes')->onDelete('cascade');
            $table->foreign('target_scene_id')->references('id')->on('virtual_tour_scenes')->onDelete('set null');
        });

        Schema::create('virtual_tour_floorplan_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->index();
            $table->unsignedBigInteger('scene_id')->index();
            $table->decimal('x', 7, 4);
            $table->decimal('y', 7, 4);
            $table->string('label', 191)->nullable();
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('virtual_tours')->onDelete('cascade');
            $table->foreign('scene_id')->references('id')->on('virtual_tour_scenes')->onDelete('cascade');
            $table->unique(['tour_id', 'scene_id']);
        });

        Schema::create('virtual_tour_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->index();
            $table->string('session_key', 80)->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('scene_views')->default(0);
            $table->unsignedInteger('hotspot_clicks')->default(0);
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('virtual_tours')->onDelete('cascade');
            $table->unique(['tour_id', 'session_key']);
        });

        Schema::create('virtual_tour_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->index();
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->unsignedBigInteger('scene_id')->nullable()->index();
            $table->unsignedBigInteger('hotspot_id')->nullable()->index();
            $table->enum('type', ['tour_view', 'scene_view', 'hotspot_click', 'share', 'qr', 'fullscreen', 'vr'])->index();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tour_id')->references('id')->on('virtual_tours')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('virtual_tour_sessions')->onDelete('set null');
            $table->foreign('scene_id')->references('id')->on('virtual_tour_scenes')->onDelete('set null');
            $table->foreign('hotspot_id')->references('id')->on('virtual_tour_hotspots')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_tour_events');
        Schema::dropIfExists('virtual_tour_sessions');
        Schema::dropIfExists('virtual_tour_floorplan_points');
        Schema::dropIfExists('virtual_tour_hotspots');
        if (Schema::hasTable('virtual_tours')) {
            Schema::table('virtual_tours', function (Blueprint $table) {
                $table->dropForeign(['start_scene_id']);
            });
        }
        Schema::dropIfExists('virtual_tour_scenes');
        Schema::dropIfExists('virtual_tours');
    }
}
