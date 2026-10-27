<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::create('advertisement_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id')->unique();
            $table->unsignedBigInteger('virtual_tour_file_id')->nullable();
            $table->string('virtual_tour_url', 1000)->nullable();
            $table->boolean('virtual_tour_enabled')->default(false);
            $table->boolean('renovation_enabled')->default(false);
            $table->string('renovation_url', 1000)->nullable();
            $table->unsignedBigInteger('rental_deposit_ceiling')->nullable();
            $table->unsignedBigInteger('rental_monthly_rent')->nullable();
            $table->boolean('mortgage_enabled')->default(false);
            $table->unsignedBigInteger('mortgage_max_amount')->nullable();
            $table->timestamps();

            $table->foreign('advertisement_id')->references('id')->on('advertisements')->onDelete('cascade');
            $table->foreign('virtual_tour_file_id')->references('id')->on('files')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('advertisement_tools');
    }
}
