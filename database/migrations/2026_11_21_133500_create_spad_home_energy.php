<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomeEnergy extends Migration
{
    public function up()
    {
        Schema::create('spad_home_energy_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title', 100);
            $table->timestamps();
        });

        Schema::create('spad_home_energy_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('location_id')->constrained('spad_home_energy_locations');
            $table->string('utility', 14);
            $table->string('title', 100);
            $table->text('identifier_encrypted');
            $table->char('identifier_hash', 64);
            $table->unsignedBigInteger('source_unit_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'utility', 'identifier_hash'], 'spad_energy_owner_unique_account');
        });

        Schema::create('spad_home_energy_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('spad_home_energy_accounts')->onDelete('cascade');
            $table->char('duplicate_hash', 64);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->date('remind_on')->nullable();
            $table->unsignedBigInteger('amount_toman');
            $table->decimal('consumption', 13, 3)->nullable();
            $table->text('payment_identifier_encrypted')->nullable();
            $table->date('paid_on')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'duplicate_hash'], 'spad_energy_unique_bill');
            $table->index(['account_id', 'due_on']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_home_energy_bills');
        Schema::dropIfExists('spad_home_energy_accounts');
        Schema::dropIfExists('spad_home_energy_locations');
    }
}
