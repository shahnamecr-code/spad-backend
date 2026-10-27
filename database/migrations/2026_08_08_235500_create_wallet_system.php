<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWalletSystem extends Migration
{
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('balance')->default(0)->comment('Toman');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'balance']);
        });

        Schema::create('wallet_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 50);
            $table->string('title', 100);
            $table->boolean('enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->longText('config_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->unsignedBigInteger('amount')->comment('Toman');
            $table->unsignedBigInteger('gateway_amount');
            $table->string('gateway_currency', 4)->default('T');
            $table->string('status', 20)->default('pending');
            $table->string('tracking_code', 100)->unique();
            $table->string('callback_token', 100)->unique();
            $table->string('transaction_id')->nullable()->index();
            $table->string('reference_id')->nullable()->index();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['wallet_id', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('gateway_id')->references('id')->on('wallet_gateways')->onDelete('set null');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('direction', 10); // credit | debit
            $table->string('type', 50); // topup | admin_credit | admin_debit | purchase | refund ...
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before')->default(0);
            $table->unsignedBigInteger('balance_after')->default(0);
            $table->string('status', 20)->default('completed');
            $table->string('reference', 150)->nullable()->unique();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['wallet_id', 'created_at']);
            $table->index(['direction', 'type']);
            $table->index(['source_type', 'source_id']);
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
        });

        Schema::create('wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        DB::table('wallet_settings')->insert([
            ['key' => 'wallet_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'topup_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'min_topup', 'value' => '100000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_topup', 'value' => '100000000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'wallet_title', 'value' => 'کیف پول SPAD', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'wallet_intro', 'value' => 'موجودی خود را افزایش دهید و تراکنش‌های مالی حساب را یکجا مدیریت کنید.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wallet_gateways')->insert([
            [
                'driver' => 'zarinpal',
                'title' => 'زرین‌پال',
                'enabled' => false,
                'is_default' => false,
                'config_encrypted' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('wallet_settings');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('wallet_gateways');
        Schema::dropIfExists('wallets');
    }
}
