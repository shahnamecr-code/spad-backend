<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class SwitchSmsToIranpayamak extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_sms_settings')) return;
        if (!Schema::hasColumn('spad_sms_settings','provider')) {
            Schema::table('spad_sms_settings',function(Blueprint $table) {
                $table->string('provider',32)->default('iranpayamak');
            });
            // Irreversible provider switch: no old credentials or event mappings.
            DB::table('spad_sms_settings')->update([
                'provider'=>'iranpayamak','enabled'=>false,
                'username'=>null,'api_key_encrypted'=>null,'sender'=>null,
                'events_json'=>'{}','updated_at'=>now(),
            ]);
        }
    }
    public function down()
    {
        // Historical delivery logs are preserved. Never restore old credentials.
    }
}
