<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadMortgageTables extends Migration
{
    public function up()
    {
        Schema::create('mortgage_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedInteger('bond_price')->default(119200);
            $table->string('bond_updated_at', 60)->default('۲۷ مهر ۱۴۰۵');
            $table->boolean('broker_enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('mortgage_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('reference', 40)->unique();
            $table->string('phone', 11);
            $table->string('city', 20);
            $table->string('household', 20);
            $table->unsignedSmallInteger('purchase_million');
            $table->unsignedSmallInteger('repair_million');
            $table->unsignedTinyInteger('term_years');
            $table->string('status', 30)->default('new')->index();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('mortgage_requests');
        Schema::dropIfExists('mortgage_settings');
    }
}
