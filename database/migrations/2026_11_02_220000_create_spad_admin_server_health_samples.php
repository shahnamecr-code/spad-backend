<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSpadAdminServerHealthSamples extends Migration {
 public function up(){
  Schema::create('spad_admin_server_health_samples',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->dateTime('sampled_at')->unique();
   $t->unsignedInteger('db_latency_ms')->nullable();
   $t->unsignedInteger('app_latency_ms')->nullable();
   $t->boolean('database_ok')->default(false);
   $t->boolean('application_ok')->default(false);
   $t->string('note',150)->nullable();
   $t->timestamps();
   $t->index('sampled_at');
  });
 }
 public function down(){Schema::dropIfExists('spad_admin_server_health_samples');}
}
