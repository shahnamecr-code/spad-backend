<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('order');
            $table->enum('type',['text','textarea','integer','boolean','select','multiselect','radio','checkbox','transaction','land_type','geo','images']);
            $table->json('enum')->nullable();
            $table->json('enumNames')->nullable();
            $table->boolean('searchable');
            $table->tinyInteger('search_level');
            $table->enum('search_type',['select','checkbox','text']);
            $table->enum('search_operator',['lt','eq','gt','like']);
            $table->boolean('show_in_crm');
            $table->json('search_enum')->nullable();
            $table->json('search_enumNames')->nullable();
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
        Schema::dropIfExists('properties');
    }
}
