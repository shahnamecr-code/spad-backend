<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadWhatsAppPersonalDelivery extends Migration
{
    public function up()
    {
        Schema::create('spad_wa_personal_config', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
        Schema::create('spad_wa_personal_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slot_key', 190)->unique();
            $table->string('kind', 20);
            $table->string('subject', 160);
            $table->string('status', 24)->default('pending');
            $table->string('message_id', 180)->nullable();
            $table->string('error_code', 90)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_wa_personal_jobs');
        Schema::dropIfExists('spad_wa_personal_config');
    }
}
