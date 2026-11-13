<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadMobileApp extends Migration
{
    public function up()
    {
        // Settings of the Android/iPhone app, edited in the admin panel.
        Schema::create('spad_app_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // One row per installed copy of the app.
        Schema::create('spad_app_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('install_id', 64)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('platform', 10);
            $table->string('app_version', 20)->nullable();
            $table->string('os_version', 40)->nullable();
            // Reserved for a push service (FCM/APNs); unused while the app polls.
            $table->text('push_token')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });

        // Messages sent to app users from the admin panel.
        Schema::create('spad_app_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 120);
            $table->text('body');
            $table->string('link', 300)->nullable();
            $table->string('audience', 10)->default('all');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();
            $table->index(['audience', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_app_notifications');
        Schema::dropIfExists('spad_app_devices');
        Schema::dropIfExists('spad_app_settings');
    }
}
