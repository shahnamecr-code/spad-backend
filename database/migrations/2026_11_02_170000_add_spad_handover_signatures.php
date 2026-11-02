<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpadHandoverSignatures extends Migration {
 public function up() {
  Schema::table('spad_handover_checklists',function(Blueprint $t){
   $t->string('user_role',12)->nullable();
   $t->text('handover_note')->nullable();
   $t->string('from_phone',15)->nullable();
   $t->string('to_phone',15)->nullable();
   $t->string('snapshot_hash',64)->nullable();
   $t->timestamp('signature_started_at')->nullable();
  });
  Schema::create('spad_handover_signatures',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->unsignedBigInteger('checklist_id')->index();
   $t->string('party',8);
   $t->string('mobile',15);
   $t->string('invitation_hash',64)->unique();
   $t->string('otp_hash',255)->nullable();
   $t->timestamp('expires_at')->nullable();
   $t->unsignedTinyInteger('attempts')->default(0);
   $t->unsignedTinyInteger('send_count')->default(0);
   $t->timestamp('last_sent_at')->nullable();
   $t->timestamp('verified_at')->nullable();
   $t->string('verification_ip',45)->nullable();
   $t->timestamps();
   $t->unique(['checklist_id','party']);
  });
 }
 public function down() {
  Schema::dropIfExists('spad_handover_signatures');
  Schema::table('spad_handover_checklists',function(Blueprint $t){
   $t->dropColumn(['user_role','handover_note','from_phone','to_phone','snapshot_hash','signature_started_at']);
  });
 }
}
