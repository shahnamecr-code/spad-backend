<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadPropertyDeposits extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->boolean('deposit_enabled')->default(false);
            $table->unsignedBigInteger('deposit_amount')->nullable();
        });

        Schema::create('property_deposit_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_id')->constrained('advertisements')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('gateway_id')->constrained('wallet_gateways')->onDelete('restrict');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('gateway_amount');
            $table->string('gateway_currency', 5);
            $table->string('status', 20)->default('pending')->index();
            $table->string('tracking_code', 70)->unique();
            $table->string('callback_token', 80);
            $table->string('transaction_id', 190)->nullable();
            $table->string('reference_id', 190)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->index(['advertisement_id','status']);
            $table->index(['user_id','created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_deposit_orders');
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn(['deposit_enabled', 'deposit_amount']);
        });
    }
}
