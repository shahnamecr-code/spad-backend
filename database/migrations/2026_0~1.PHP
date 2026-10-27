<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBelowExpertPriceToAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->boolean('below_expert_price')->default(false)->after('documents_verified');
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn('below_expert_price');
        });
    }
}
