<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeDoctorCategoryOverrides extends Migration
{
    public function up()
    {
        Schema::create('spad_home_doctor_category_overrides', function (Blueprint $table) {
            $table->string('code', 32)->primary();
            $table->boolean('enabled')->default(true);
            $table->string('label', 100)->nullable();
            $table->string('description', 300)->nullable();
            $table->text('guide')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_home_doctor_category_overrides');
    }
}
