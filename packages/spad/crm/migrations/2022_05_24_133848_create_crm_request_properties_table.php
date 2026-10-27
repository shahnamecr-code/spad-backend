<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmRequestPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_request_properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_slug');
            $table->foreignId('crm_request_id')->constrained();
            $table->string('type');
            $table->string('text_value')->nullable();
            $table->bigInteger('integer_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->longText('textarea_value')->nullable();
            $table->json('json_value')->nullable();
            $table->timestamps();
            $table->foreign('property_slug')->references('slug')->on('properties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_request_properties');
    }
}
