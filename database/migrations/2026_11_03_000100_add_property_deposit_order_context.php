<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPropertyDepositOrderContext extends Migration
{
    public function up()
    {
        Schema::table('property_deposit_orders', function (Blueprint $table) {
            $table->string('property_code', 60)->nullable()->index();
            $table->string('property_title', 250)->nullable();
            $table->string('property_location', 250)->nullable();
            $table->string('gateway_title', 120)->nullable();
        });
    }

    public function down()
    {
        Schema::table('property_deposit_orders', function (Blueprint $table) {
            $table->dropIndex(['property_code']);
            $table->dropColumn(['property_code','property_title','property_location','gateway_title']);
        });
    }
}
