<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadBuildingManagerVerifications extends Migration {
 public function up() {
  Schema::create('spad_building_manager_verifications',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->unsignedBigInteger('partner_panel_id')->unique();
   $t->unsignedBigInteger('user_id')->index();
   $t->string('full_name',180);
   $t->text('iban_encrypted');
   $t->string('identity_document_path',550);
   $t->string('identity_document_name',180);
   $t->string('support_document_path',550)->nullable();
   $t->string('support_document_name',180)->nullable();
   $t->string('status',24)->default('pending')->index();
   $t->string('review_note',1000)->nullable();
   $t->unsignedBigInteger('reviewed_by_admin_id')->nullable();
   $t->timestamp('reviewed_at')->nullable();
   $t->timestamps();
  });
 }
 public function down(){Schema::dropIfExists('spad_building_manager_verifications');}
}
