<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpadCustomerSmsFeatures extends Migration
{
    public function up()
    {
        if (Schema::hasTable('spad_crm_contacts') && !Schema::hasColumn('spad_crm_contacts','budget_toman')) {
            Schema::table('spad_crm_contacts',function(Blueprint $table){
                $table->unsignedBigInteger('budget_toman')->nullable()->index();
            });
        }
        if (Schema::hasTable('spad_sms_settings')) {
            if (!Schema::hasColumn('spad_sms_settings','admin_mobile')) {
                Schema::table('spad_sms_settings',function(Blueprint $table) {
                    $table->string('admin_mobile',20)->nullable();
                });
            }
            if (!Schema::hasColumn('spad_sms_settings','admin_notify_enabled')) {
                Schema::table('spad_sms_settings',function(Blueprint $table) {
                    $table->boolean('admin_notify_enabled')->default(false);
                });
            }
        }
        if (!Schema::hasTable('spad_customer_sms_outbox')) {
            Schema::create('spad_customer_sms_outbox',function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('contact_id')->index();
                $table->unsignedBigInteger('advertisement_id')->nullable()->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('kind',32)->index();
                $table->text('message')->nullable();
                $table->string('status',24)->default('pending')->index();
                $table->string('dedup_hash',64)->unique();
                $table->string('provider_reference',100)->nullable();
                $table->string('error_code',70)->nullable();
                $table->timestamp('attempted_at')->nullable();
                $table->timestamps();
                $table->index(['status','created_at'],'spad_sms_outbox_status_created');
            });
        }
    }
    public function down()
    {
        // Preserve audit trails, customers and opt-outs.
    }
}
