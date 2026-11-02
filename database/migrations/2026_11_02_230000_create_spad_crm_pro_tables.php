<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSpadCrmProTables extends Migration {
 public function up() {
  Schema::create('spad_crm_contacts',function(Blueprint $t){
   $t->bigIncrements('id');$t->string('full_name',150);$t->string('mobile',18)->unique();
   $t->string('email',190)->nullable();$t->string('source',50)->default('manual');
   $t->string('stage',32)->default('new')->index();$t->string('interest',35)->default('buy');
   $t->string('city',90)->nullable();$t->string('budget',90)->nullable();$t->string('preferred_location',170)->nullable();
   $t->unsignedTinyInteger('priority')->default(2);
   $t->unsignedBigInteger('assigned_to')->nullable()->index();
   $t->unsignedBigInteger('created_by')->nullable();
   $t->boolean('sms_consent')->default(false);$t->boolean('voice_consent')->default(false);
   $t->timestamp('consent_recorded_at')->nullable();$t->string('consent_source',120)->nullable();
   $t->timestamp('opted_out_at')->nullable();$t->timestamp('last_contact_at')->nullable();
   $t->text('note')->nullable();$t->timestamps();$t->softDeletes();
  });
  Schema::create('spad_crm_followups',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('contact_id')->index();
   $t->unsignedBigInteger('admin_id')->index();$t->string('title',170);
   $t->string('kind',25)->default('call');$t->string('status',25)->default('pending');
   $t->dateTime('due_at')->index();$t->timestamp('done_at')->nullable();$t->text('description')->nullable();$t->timestamps();
  });
  Schema::create('spad_crm_activities',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('contact_id')->index();$t->unsignedBigInteger('admin_id')->nullable();
   $t->string('type',45);$t->text('content')->nullable();$t->json('metadata')->nullable();$t->timestamps();
  });
  Schema::create('spad_crm_outbound',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('contact_id')->index();$t->unsignedBigInteger('admin_id')->index();
   $t->string('channel',16);$t->string('status',25)->default('pending');$t->string('provider',45);
   $t->text('message');$t->string('provider_ref',140)->nullable();$t->string('failure_code',70)->nullable();
   $t->timestamp('attempted_at')->nullable();$t->timestamps();
   $t->index(['contact_id','channel','created_at'],'spad_crm_outbound_contact_channel');
  });
 }
 public function down(){
  foreach(['spad_crm_outbound','spad_crm_activities','spad_crm_followups','spad_crm_contacts'] as $name)Schema::dropIfExists($name);
 }
}
