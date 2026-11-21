<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadSunlightProjects extends Migration
{
    public function up()
    {
        Schema::create('spad_sunlight_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 120);
            $table->json('input');
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_sunlight_projects');
    }
}
