<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisementPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisement_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->onDelete('cascade');
            $table->string('property_slug');
            $table->foreign('property_slug')->references('slug')->on('properties')->onDelete('cascade');
            $table->string('text_value')->nullable();
            $table->bigInteger('integer_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->longText('textarea_value')->nullable();
            $table->json('json_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('advertisement_properties');
    }
}
