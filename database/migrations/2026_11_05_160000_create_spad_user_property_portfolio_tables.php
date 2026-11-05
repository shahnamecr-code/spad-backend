<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadUserPropertyPortfolioTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_user_property_assets')) {
            Schema::create('spad_user_property_assets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->char('postal_hash', 64);
                $table->text('postal_cipher');
                $table->text('address_cipher');
                $table->string('nickname', 100);
                $table->string('province', 100);
                $table->string('city', 100);
                $table->unsignedBigInteger('place_id');
                $table->string('place_name', 140);
                $table->string('match_level', 20);
                $table->string('property_kind', 20);
                $table->decimal('area_m2', 10, 2);
                $table->string('rooms', 15)->nullable();
                $table->string('age', 15)->nullable();
                $table->string('parking', 10)->nullable();
                $table->string('elevator', 10)->nullable();
                $table->unsignedBigInteger('price_per_m2_toman')->nullable();
                $table->unsignedBigInteger('estimated_total_toman')->nullable();
                $table->unsignedBigInteger('range_low_toman')->nullable();
                $table->unsignedBigInteger('range_high_toman')->nullable();
                $table->unsignedInteger('data_points')->nullable();
                $table->string('coverage', 20)->default('unknown');
                $table->string('source_computed_at', 64)->nullable();
                $table->string('source_url', 255)->default('https://nemuda.com/');
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('last_success_at')->nullable();
                $table->string('refresh_error', 60)->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'postal_hash'], 'spad_asset_owner_postal_unique');
                $table->index(['user_id', 'last_checked_at'], 'spad_asset_refresh_idx');
            });
        }
        if (!Schema::hasTable('spad_user_property_prices')) {
            Schema::create('spad_user_property_prices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('asset_id');
                $table->unsignedBigInteger('price_per_m2_toman');
                $table->unsignedBigInteger('estimated_total_toman');
                $table->string('source_computed_at', 64)->nullable();
                $table->timestamp('captured_at');
                $table->foreign('asset_id', 'spad_asset_history_fk')
                    ->references('id')->on('spad_user_property_assets')->onDelete('cascade');
                $table->index(['asset_id', 'captured_at'], 'spad_asset_history_time_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('spad_user_property_prices');
        Schema::dropIfExists('spad_user_property_assets');
    }
}
