<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRentalOccupancyToAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->string('rental_occupancy_status', 20)->nullable()->after('rental_monthly_rent');
            $table->date('rental_evacuation_date')->nullable()->after('rental_occupancy_status');
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn(['rental_occupancy_status', 'rental_evacuation_date']);
        });
    }
}
