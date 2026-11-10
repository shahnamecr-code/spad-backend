<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadWaPreviewBuildQueue extends Migration
{
    public function up()
    {
        Schema::create('spad_wa_preview_build_queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target_key', 68)->unique();
            $table->string('kind', 12);
            $table->string('province', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();
            $table->index(['state', 'requested_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_wa_preview_build_queue');
    }
}
