<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadAuctionDeposits extends Migration {
 public function up() {
  Schema::table('spad_community_settings',function(Blueprint $t){
   $t->boolean('auction_deposit_enabled')->default(false);
   $t->unsignedBigInteger('auction_deposit_toman')->default(0);
   $t->text('auction_deposit_terms')->nullable();
  });
  Schema::create('spad_auction_participations',function(Blueprint $t){
   $t->bigIncrements('id');
   $t->unsignedBigInteger('auction_id')->index();
   $t->unsignedBigInteger('user_id')->index();
   $t->unsignedBigInteger('amount_toman');
   $t->string('status',20)->default('held')->index();
   $t->unsignedBigInteger('wallet_debit_id')->nullable();
   $t->unsignedBigInteger('wallet_refund_id')->nullable();
   $t->timestamp('refunded_at')->nullable();
   $t->unsignedBigInteger('refunded_by_admin_id')->nullable();
   $t->string('refund_reason',1000)->nullable();
   $t->timestamps();
   $t->unique(['auction_id','user_id'],'spad_auction_participant_unique');
  });
 }
 public function down() {
  Schema::dropIfExists('spad_auction_participations');
  Schema::table('spad_community_settings',function(Blueprint $t){
   $t->dropColumn(['auction_deposit_enabled','auction_deposit_toman','auction_deposit_terms']);
  });
 }
}
