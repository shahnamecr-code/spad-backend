<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHouseholdReminders extends Migration
{
    public function up(): void
    {
        Schema::create('spad_household_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('service', 16);
            $table->string('request_key', 72);
            $table->string('title', 100);
            $table->dateTime('due_at');
            $table->string('status', 16)->default('scheduled');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'request_key'], 'spad_household_reminder_request');
            $table->index(['status', 'due_at'], 'spad_household_reminder_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spad_household_reminders');
    }
}
