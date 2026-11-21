<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeGardenTables extends Migration
{
    public function up()
    {
        Schema::create('spad_garden_plants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 90);
            $table->string('location_label', 100)->nullable();
            $table->string('light', 16)->default('unknown');
            $table->string('species', 120)->nullable();
            $table->string('scientific_name', 120)->nullable();
            $table->dateTime('species_confirmed_at')->nullable();
            $table->unsignedTinyInteger('water_every_days');
            $table->dateTime('next_water_at');
            $table->uuid('water_cycle')->unique();
            $table->boolean('reminder_enabled')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'archived_at', 'next_water_at'], 'spad_garden_owner_schedule');
        });
        Schema::create('spad_garden_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plant_id');
            $table->foreign('plant_id')->references('id')->on('spad_garden_plants')->cascadeOnDelete();
            $table->string('stage', 16);
            $table->string('caption', 200)->nullable();
            $table->string('path', 170);
            $table->dateTime('created_at');
            $table->index(['plant_id', 'created_at']);
        });
        Schema::create('spad_garden_activity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('plant_id');
            $table->foreign('plant_id')->references('id')->on('spad_garden_plants')->cascadeOnDelete();
            $table->string('kind', 24);
            $table->uuid('water_cycle')->nullable();
            $table->unsignedTinyInteger('snooze_days')->nullable();
            $table->dateTime('next_water_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->decimal('height_cm', 6, 1)->nullable();
            $table->dateTime('created_at');
            $table->unique(['plant_id', 'water_cycle'], 'spad_garden_water_once');
            $table->index(['plant_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_garden_activity');
        Schema::dropIfExists('spad_garden_photos');
        Schema::dropIfExists('spad_garden_plants');
    }
}
