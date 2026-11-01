<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShirazMapCoordinatesToAdvertisementTools extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->decimal('map_lat', 9, 6)->nullable();
            $table->decimal('map_lng', 9, 6)->nullable();
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn(['map_lat', 'map_lng']);
        });
    }
}
