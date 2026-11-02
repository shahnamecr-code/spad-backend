<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadAiAssistantSettings extends Migration
{
    public function up()
    {
        Schema::create('spad_ai_assistant_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('enabled')->default(false);
            $table->string('title', 100)->default('دستیار هوشمند ملکی اسپاد');
            $table->string('model', 80)->default('gpt-4.1-mini');
            $table->text('api_key_encrypted')->nullable();
            $table->text('custom_instructions')->nullable();
            $table->unsignedSmallInteger('daily_limit')->default(30);
            $table->unsignedSmallInteger('max_output_tokens')->default(450);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('spad_ai_assistant_settings');
    }
}
