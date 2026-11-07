<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadSmsCentre extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_sms_settings')) {
            Schema::create('spad_sms_settings', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->boolean('enabled')->default(false);
                $table->string('username', 120)->nullable();
                $table->text('api_key_encrypted')->nullable();
                $table->string('sender', 30)->nullable();
                $table->unsignedInteger('daily_limit')->default(300);
                $table->text('events_json')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('spad_sms_delivery_logs')) {
            Schema::create('spad_sms_delivery_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('event', 70);
                $table->string('mobile_masked', 25);
                $table->string('status', 24);
                $table->string('provider_reference', 100)->nullable();
                $table->string('dedup_hash', 64)->nullable()->unique();
                $table->string('error_code', 80)->nullable();
                $table->timestamps();
                $table->index(['created_at', 'event']);
            });
        }
    }
    public function down()
    {
        // Preserve delivery audit and encrypted credentials on rollback.
        // Dropping these tables requires a deliberate data-retention decision.
    }
}
