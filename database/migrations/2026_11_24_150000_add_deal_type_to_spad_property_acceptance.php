<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddDealTypeToSpadPropertyAcceptance extends Migration
{
    public function up()
    {
        if (Schema::hasTable('spad_property_acceptance_requests') && !Schema::hasColumn('spad_property_acceptance_requests','deal_type')) {
            Schema::table('spad_property_acceptance_requests',function(Blueprint $table){
                $table->string('deal_type',10)->default('sell')->index();
            });
        }
    }
    public function down()
    {
        // Keep request type and existing customer leads intact on code rollback.
    }
}
