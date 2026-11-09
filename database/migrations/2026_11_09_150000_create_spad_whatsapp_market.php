<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadWhatsappMarket extends Migration
{
    public function up()
    {
        Schema::create('spad_wa_market_config', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->json('schedule_json')->nullable();
            $table->json('cities_json')->nullable();
            $table->json('groups_json')->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->string('phone_number_id', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('spad_wa_market_publications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slot_key', 190)->unique();
            $table->string('kind', 20);
            $table->string('subject', 160);
            $table->string('status', 25)->default('queued');
            $table->text('poster_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta_json')->nullable();
            $table->json('delivery_json')->nullable();
            $table->timestamps();
            $table->index(['status','created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_wa_market_publications');
        Schema::dropIfExists('spad_wa_market_config');
    }
}
