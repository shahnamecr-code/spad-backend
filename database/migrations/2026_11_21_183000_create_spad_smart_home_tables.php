<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadSmartHomeTables extends Migration
{
    public function up(): void
    {
        Schema::create('spad_smart_home_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('gateway', 50);
            $table->string('configuration', 64);
            $table->uuid('version');
            $table->text('encrypted_credential');
            $table->dateTime('authenticated_at');
            $table->dateTime('expires_at');
            $table->timestamps();
        });

        Schema::create('spad_smart_home_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('request_id');
            $table->uuid('connection_version')->nullable();
            $table->string('event', 30);
            $table->string('entity_id', 100)->nullable();
            $table->string('device_label', 112)->nullable();
            $table->string('desired_state', 3)->nullable();
            $table->string('result', 30);
            $table->string('observed_state', 12)->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'request_id'], 'spad_smart_home_audit_request');
            $table->index(['user_id', 'id'], 'spad_smart_home_audit_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spad_smart_home_audit');
        Schema::dropIfExists('spad_smart_home_connections');
    }
}
