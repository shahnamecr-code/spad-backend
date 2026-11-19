<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpadAppNotificationRevocations extends Migration
{
    public function up()
    {
        Schema::table('spad_app_notifications', function (Blueprint $table) {
            $table->softDeletes();
            $table->unsignedBigInteger('revoked_by_admin_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('spad_app_notifications', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'revoked_by_admin_id']);
        });
    }
}
