<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationBadgesToAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->boolean('owner_identity_verified')->default(false)->after('mortgage_max_amount');
            $table->boolean('documents_verified')->default(false)->after('owner_identity_verified');
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn(['owner_identity_verified', 'documents_verified']);
        });
    }
}
