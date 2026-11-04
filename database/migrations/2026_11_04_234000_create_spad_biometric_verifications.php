<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadBiometricVerifications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('spad_biometric_verifications')) return;
        Schema::create('spad_biometric_verifications',function(Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unique();
            // Cryptographic link to the current base KYC; not a national code.
            $table->string('base_national_hash',64);
            $table->string('phone_hash',64);
            $table->string('provider',48)->default('api_ir_videolive');
            $table->string('status',24)->default('not_checked');
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status','expires_at']);
        });
    }

    public function down()
    {
        // Non-destructive for privacy/verification audit. Explicit audited
        // deletion must be implemented separately with a retention policy.
    }
}
