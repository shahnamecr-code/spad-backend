<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFloorplan3dToAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->boolean('floorplan_3d_enabled')->default(false)->after('renovation_url');
            $table->text('floorplan_3d_url')->nullable()->after('floorplan_3d_enabled');
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn(['floorplan_3d_enabled', 'floorplan_3d_url']);
        });
    }
}
