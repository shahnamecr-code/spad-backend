<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInquirySystem extends Migration
{
    public function up()
    {
        Schema::create('inquiry_services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 190);
            $table->string('slug', 190)->unique();
            $table->string('category', 120)->nullable()->index();
            $table->string('icon')->nullable();
            $table->string('accent', 30)->nullable();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->enum('execution_type', ['api', 'manual'])->default('manual')->index();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->json('form_schema')->nullable();
            $table->longText('api_config_encrypted')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('featured')->default(false)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inquiry_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('inquiry_service_id')->index();
            $table->string('reference', 80)->unique();
            $table->unsignedBigInteger('amount')->default(0);
            $table->enum('payment_method', ['wallet', 'gateway', 'free'])->nullable()->index();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->enum('status', ['pending_payment', 'waiting_operator', 'processing', 'completed', 'failed', 'rejected', 'cancelled'])->default('pending_payment')->index();
            $table->unsignedBigInteger('gateway_id')->nullable()->index();
            $table->unsignedBigInteger('gateway_amount')->nullable();
            $table->string('gateway_currency', 5)->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->string('callback_token', 100)->nullable();
            $table->string('gateway_reference_id')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
            $table->json('input_data')->nullable();
            $table->json('result_data')->nullable();
            $table->longText('result_text')->nullable();
            $table->text('operator_note')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('inquiry_service_id')->references('id')->on('inquiry_services')->onDelete('restrict');
            $table->foreign('gateway_id')->references('id')->on('wallet_gateways')->onDelete('set null');
        });

        Schema::create('inquiry_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_request_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('event', 100);
            $table->string('message', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->foreign('inquiry_request_id')->references('id')->on('inquiry_requests')->onDelete('cascade');
        });

        Schema::create('inquiry_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiry_request_logs');
        Schema::dropIfExists('inquiry_requests');
        Schema::dropIfExists('inquiry_services');
        Schema::dropIfExists('inquiry_settings');
    }
}
