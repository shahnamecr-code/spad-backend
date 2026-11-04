<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddPersonInfoToSpadIdentity extends Migration
{
    public function up(){
        if(!Schema::hasTable('spad_user_verifications'))throw new RuntimeException('SPAD identity tables not installed.');
        Schema::table('spad_user_verifications',function(Blueprint $t){
            $t->text('birth_date_encrypted')->nullable();
            $t->string('person_info_status',24)->default('not_checked');
            $t->timestamp('person_info_checked_at')->nullable();
        });
    }
    public function down(){
        if(Schema::hasTable('spad_user_verifications'))
            Schema::table('spad_user_verifications',function(Blueprint $t){
                $t->dropColumn(['birth_date_encrypted','person_info_status','person_info_checked_at']);
            });
    }
}
