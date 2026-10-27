<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentTypeToAdvertisementToolsTable extends Migration
{
    public function up()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->string('document_type', 40)->nullable()->after('mortgage_max_amount');
        });
    }

    public function down()
    {
        Schema::table('advertisement_tools', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
    }
}
