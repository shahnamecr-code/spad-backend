<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePropertyRaffleSystem extends Migration
{
    public function up()
    {
        Schema::create('property_raffles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id');
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->longText('terms')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->dateTime('draw_at')->nullable();
            $table->dateTime('drawn_at')->nullable();
            $table->unsignedInteger('capacity');
            $table->unsignedBigInteger('ticket_price')->default(0)->comment('Toman');
            $table->unsignedInteger('max_tickets_per_user')->default(1);
            $table->string('status')->default('draft');
            $table->string('hero_image')->nullable();
            $table->string('panorama_image')->nullable();
            $table->string('virtual_tour_url')->nullable();
            $table->boolean('live_enabled')->default(false);
            $table->string('live_url')->nullable();
            $table->dateTime('live_start_at')->nullable();
            $table->json('highlights')->nullable();
            $table->unsignedBigInteger('ticket_design_id')->nullable();
            $table->unsignedBigInteger('winner_ticket_id')->nullable();
            $table->unsignedBigInteger('draw_operator_id')->nullable();
            $table->text('winner_announcement')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
            $table->foreign('advertisement_id')->references('id')->on('advertisements')->onDelete('cascade');
            $table->foreign('draw_operator_id')->references('id')->on('admins')->onDelete('set null');
        });

        Schema::create('raffle_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('title');
            $table->boolean('enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->longText('config_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('raffle_ticket_designs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('raffle_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('raffle_id');
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->unsignedInteger('ticket_count')->default(1);
            $table->unsignedBigInteger('amount')->default(0)->comment('Toman');
            $table->unsignedBigInteger('gateway_amount')->default(0);
            $table->string('gateway_currency', 4)->default('T');
            $table->string('status')->default('pending');
            $table->string('tracking_code')->unique();
            $table->string('callback_token', 80)->unique();
            $table->string('transaction_id')->nullable()->index();
            $table->string('reference_id')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['raffle_id', 'status']);
            $table->index(['user_id', 'raffle_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('raffle_id')->references('id')->on('property_raffles')->onDelete('cascade');
            $table->foreign('gateway_id')->references('id')->on('raffle_gateways')->onDelete('set null');
        });

        Schema::create('raffle_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raffle_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('sequence_no');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->dateTime('purchased_at');
            $table->timestamps();

            $table->unique(['raffle_id', 'sequence_no']);
            $table->index(['raffle_id', 'status']);
            $table->index(['user_id', 'raffle_id']);
            $table->foreign('raffle_id')->references('id')->on('property_raffles')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('raffle_orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('raffle_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        DB::table('raffle_settings')->insert([
            ['key' => 'page_title', 'value' => 'قرعه‌کشی ویژه ملک', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'page_intro', 'value' => 'فرصتی متفاوت برای ورود به قرعه‌کشی یک ملک ویژه.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_notice', 'value' => 'پس از پرداخت موفق، بلیط و کد شرکت در قرعه‌کشی در پنل کاربری شما ثبت می‌شود.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'reservation_minutes', 'value' => '15', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('raffle_gateways')->insert([
            ['driver' => 'local', 'title' => 'درگاه تست داخلی', 'enabled' => false, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['driver' => 'zarinpal', 'title' => 'زرین‌پال', 'enabled' => false, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('raffle_settings');
        Schema::dropIfExists('raffle_tickets');
        Schema::dropIfExists('raffle_orders');
        Schema::dropIfExists('raffle_ticket_designs');
        Schema::dropIfExists('raffle_gateways');
        Schema::dropIfExists('property_raffles');
    }
}
