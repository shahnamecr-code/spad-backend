<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSpadKycAndBuildingOperations extends Migration {
 public function up() {
  Schema::create('spad_user_verifications',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('user_id')->unique();
   $t->text('national_code_encrypted')->nullable();
   $t->string('national_code_hash',64)->nullable();
   $t->string('full_name',180)->nullable();
   $t->string('phone',15)->nullable();
   $t->boolean('phone_verified')->default(false);
   $t->timestamp('phone_verified_at')->nullable();
   $t->string('phone_code_hash',255)->nullable();
   $t->timestamp('phone_code_expires_at')->nullable();
   $t->unsignedTinyInteger('phone_attempts')->default(0);
   $t->unsignedTinyInteger('phone_sends')->default(0);
   $t->timestamp('phone_last_sent_at')->nullable();
   $t->string('shahkar_status',24)->default('not_checked');
   $t->timestamp('shahkar_checked_at')->nullable();
   $t->string('status',25)->default('draft')->index();
   $t->unsignedTinyInteger('level')->default(0);
   $t->unsignedTinyInteger('requested_level')->default(1);
   $t->text('review_note')->nullable();
   $t->unsignedBigInteger('reviewed_by')->nullable();
   $t->timestamp('reviewed_at')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_user_verification_documents',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('verification_id')->index();
   $t->unsignedBigInteger('user_id')->index();
   $t->string('kind',35);
   $t->string('path',500);$t->string('filename',190);
   $t->string('mime',90);$t->unsignedInteger('bytes');
   $t->timestamps();$t->unique(['verification_id','kind']);
  });
  Schema::create('spad_building_facilities',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->string('title',150);$t->text('description')->nullable();
   $t->boolean('active')->default(true);$t->timestamps();
  });
  Schema::create('spad_building_reservations',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->unsignedBigInteger('facility_id')->index();$t->unsignedBigInteger('unit_id')->index();
   $t->unsignedBigInteger('user_id')->index();$t->dateTime('starts_at')->index();
   $t->dateTime('ends_at');$t->string('status',20)->default('approved');$t->timestamps();
  });
  Schema::create('spad_building_maintenance',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->string('title',180);$t->string('category',45)->default('other');
   $t->date('due_date')->index();$t->string('status',20)->default('open');
   $t->text('notes')->nullable();$t->unsignedBigInteger('estimated_cost_toman')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_building_polls',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->string('question',350);$t->timestamp('closes_at')->nullable();
   $t->string('status',20)->default('open');$t->timestamps();
  });
  Schema::create('spad_building_poll_votes',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('poll_id')->index();
   $t->unsignedBigInteger('building_id')->index();$t->unsignedBigInteger('user_id')->index();
   $t->unsignedBigInteger('unit_id')->index();$t->string('vote',12);$t->timestamps();
   $t->unique(['poll_id','unit_id']);
  });
 }
 public function down(){
  foreach(['spad_building_poll_votes','spad_building_polls','spad_building_maintenance','spad_building_reservations','spad_building_facilities','spad_user_verification_documents','spad_user_verifications'] as $table)Schema::dropIfExists($table);
 }
}
