<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadCrmBehaviorTables extends Migration
{
    public function up()
    {
        Schema::create('spad_crm_behavior_consents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('spad_crm_behavior_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('event_type', 30);
            $table->string('page_path', 220)->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('tour_id')->nullable();
            $table->unsignedBigInteger('scene_id')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at'], 'spad_behavior_user_time');
            $table->index(['property_id', 'event_type', 'created_at'], 'spad_behavior_property_type_time');
            $table->index(['created_at'], 'spad_behavior_retention');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_crm_behavior_events');
        Schema::dropIfExists('spad_crm_behavior_consents');
    }
}
