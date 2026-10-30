<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractDetailsToMortgageRequests extends Migration
{
    public function up()
    {
        Schema::table('mortgage_requests', function (Blueprint $table) {
            $table->string('applicant_name', 150)->nullable();
            $table->string('national_id', 10)->nullable();
            $table->string('contract_number', 100)->nullable();
            $table->string('contract_date', 30)->nullable();
            $table->string('buyer_name', 150)->nullable();
            $table->string('seller_name', 150)->nullable();
            $table->string('property_address', 1200)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->unsignedBigInteger('deal_price_toman')->nullable();
            $table->string('contract_attachment', 255)->nullable();
            $table->text('applicant_note')->nullable();
        });
    }

    public function down()
    {
        Schema::table('mortgage_requests', function (Blueprint $table) {
            $table->dropColumn(['applicant_name','national_id','contract_number','contract_date','buyer_name','seller_name','property_address','postal_code','deal_price_toman','contract_attachment','applicant_note']);
        });
    }
}
