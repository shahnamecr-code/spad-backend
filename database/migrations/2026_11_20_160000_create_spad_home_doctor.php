<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeDoctor extends Migration
{
    public function up()
    {
        Schema::create('spad_home_doctor_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('category', 32);
            $table->string('status', 16)->default('draft');
            $table->text('description');
            $table->json('answers')->nullable();
            $table->json('result')->nullable();
            $table->uuid('analysis_attempt')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('analysis_started_at')->nullable();
            $table->unsignedTinyInteger('feedback_rating')->nullable();
            $table->text('feedback_comment')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['category', 'status']);
        });

        Schema::create('spad_home_doctor_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('path');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->timestamps();
            $table->foreign('case_id')->references('id')->on('spad_home_doctor_cases')->onDelete('cascade');
        });

        Schema::create('spad_home_doctor_followups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->unsignedTinyInteger('sequence');
            $table->text('question');
            $table->string('status', 16)->default('queued');
            $table->json('result')->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('analysis_started_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'sequence']);
            $table->foreign('case_id')->references('id')->on('spad_home_doctor_cases')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_home_doctor_followups');
        Schema::dropIfExists('spad_home_doctor_media');
        Schema::dropIfExists('spad_home_doctor_cases');
    }
}
