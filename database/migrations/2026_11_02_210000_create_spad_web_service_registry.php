<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSpadWebServiceRegistry extends Migration {
 public function up() {
  Schema::create('spad_web_service_connections',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->string('code',80)->unique();
   $t->string('title',160);
   $t->string('category',40)->index();
   $t->string('provider',120)->nullable();
   $t->string('endpoint',500)->nullable();
   $t->text('description')->nullable();
   $t->json('config_public')->nullable();
   $t->text('secrets_encrypted')->nullable();
   $t->boolean('enabled')->default(false);
   $t->string('mode',24)->default('planned');
   $t->string('health_status',28)->default('not_tested');
   $t->text('last_note')->nullable();
   $t->timestamp('last_checked_at')->nullable();
   $t->unsignedBigInteger('updated_by')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_web_service_audit',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->unsignedBigInteger('connection_id')->index();
   $t->unsignedBigInteger('admin_id')->nullable();
   $t->string('action',40);
   $t->text('note')->nullable();
   $t->timestamps();
  });
 }
 public function down(){
  Schema::dropIfExists('spad_web_service_audit');
  Schema::dropIfExists('spad_web_service_connections');
 }
}
