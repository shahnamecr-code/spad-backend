<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfferRangeToSpadMaterials extends Migration
{
    public function up()
    {
        if (Schema::hasTable('material_price_products') &&
            !Schema::hasColumn('material_price_products', 'price_max_toman')) {
            Schema::table('material_price_products', function (Blueprint $table) {
                $table->unsignedBigInteger('price_max_toman')->nullable()->after('price_toman');
            });
        }
    }
    public function down()
    {
        if (Schema::hasTable('material_price_products') &&
            Schema::hasColumn('material_price_products', 'price_max_toman')) {
            Schema::table('material_price_products', function (Blueprint $table) {
                $table->dropColumn('price_max_toman');
            });
        }
    }
}
