<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeServiceOperations extends Migration
{
    public function up()
    {
        Schema::create('spad_home_service_settings', function (Blueprint $table) {
            $table->string('service', 32)->primary();
            $table->boolean('enabled')->default(true);
            $table->boolean('writes_enabled')->default(true);
            $table->unsignedSmallInteger('writes_per_minute')->default(18);
            $table->string('guide', 600)->nullable();
            $table->timestamps();
        });
        Schema::create('spad_home_service_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service', 32);
            $table->string('event', 32);
            $table->string('actor_hash', 64)->nullable();
            $table->unsignedSmallInteger('http_status');
            $table->unsignedInteger('duration_ms');
            $table->timestamp('created_at');
            $table->index(['service', 'created_at']);
        });
        Schema::create('spad_home_service_admin_audit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service', 32)->index();
            $table->unsignedBigInteger('admin_id');
            $table->text('changes');
            $table->timestamp('created_at');
        });

        foreach (['scan', 'doctor', 'neighbors', 'league'] as $service) {
            DB::table('spad_home_service_settings')->insert([
                'service' => $service, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('spad_home_service_admin_audit');
        Schema::dropIfExists('spad_home_service_events');
        Schema::dropIfExists('spad_home_service_settings');
    }
}
