<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiIrVerificationProviderToSpadKyc extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('spad_user_verifications'))
            throw new RuntimeException('SPAD KYC table not installed.');
        if(!Schema::hasColumn('spad_user_verifications','verification_provider')){
            Schema::table('spad_user_verifications',function(Blueprint $table){
                $table->string('verification_provider',24)->nullable()->index();
            });
        }
    }
    public function down()
    {
        if(Schema::hasTable('spad_user_verifications') &&
            Schema::hasColumn('spad_user_verifications','verification_provider')){
            Schema::table('spad_user_verifications',function(Blueprint $table){
                $table->dropIndex(['verification_provider']);
                $table->dropColumn('verification_provider');
            });
        }
    }
}
