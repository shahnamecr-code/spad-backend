<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitUtilityAndTaxReferences extends Migration
{
    public function up()
    {
        Schema::table('spad_building_units', function (Blueprint $table) {
            $table->string('water_bill_id', 40)->nullable();
            $table->string('water_subscription_no', 50)->nullable();
            $table->string('electricity_bill_id', 40)->nullable();
            $table->string('electricity_subscription_no', 50)->nullable();
            $table->string('gas_bill_id', 40)->nullable();
            $table->string('gas_subscription_no', 50)->nullable();
            $table->string('landline_number', 25)->nullable();
            $table->string('landline_bill_id', 40)->nullable();
            $table->string('municipal_renovation_code', 50)->nullable();
            $table->string('municipal_property_code', 50)->nullable();
            $table->string('deed_main_no', 50)->nullable();
            $table->string('deed_sub_no', 50)->nullable();
            $table->string('deed_registration_section', 50)->nullable();
            $table->decimal('deed_area_sqm', 12, 2)->nullable();
            $table->string('deed_unique_id', 60)->nullable();
            $table->string('tax_file_reference', 80)->nullable();
        });

        Schema::table('spad_building_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->string('payment_identifier', 60)->nullable();
            $table->string('tax_category', 40)->nullable();
        });
    }

    public function down()
    {
        Schema::table('spad_building_bills', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'payment_identifier', 'tax_category']);
        });
        Schema::table('spad_building_units', function (Blueprint $table) {
            $table->dropColumn([
                'water_bill_id', 'water_subscription_no',
                'electricity_bill_id', 'electricity_subscription_no',
                'gas_bill_id', 'gas_subscription_no',
                'landline_number', 'landline_bill_id',
                'municipal_renovation_code', 'municipal_property_code',
                'deed_main_no', 'deed_sub_no', 'deed_registration_section',
                'deed_area_sqm', 'deed_unique_id', 'tax_file_reference',
            ]);
        });
    }
}
