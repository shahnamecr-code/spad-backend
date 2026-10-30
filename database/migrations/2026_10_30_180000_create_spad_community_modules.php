<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSpadCommunityModules extends Migration {
 public function up() {
  Schema::create('spad_community_settings',function(Blueprint $t){
   $t->unsignedBigInteger('id')->primary();
   $t->boolean('buildings_enabled')->default(true);
   $t->boolean('builders_enabled')->default(true);
   $t->boolean('auctions_enabled')->default(true);
   $t->unsignedBigInteger('minimum_bid_increment')->default(10000000);
   $t->text('auction_notice')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_buildings',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('owner_user_id')->index();
   $t->string('name',180);$t->string('city',110);$t->string('address',600);
   $t->unsignedSmallInteger('unit_count');$t->string('status',24)->default('pending')->index();
   $t->unsignedBigInteger('default_charge_toman')->default(0);
   $t->timestamps();
  });
  Schema::create('spad_building_units',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->unsignedBigInteger('user_id')->nullable()->index();$t->string('label',80);
   $t->string('resident_name',150)->nullable();$t->string('status',20)->default('vacant');
   $t->timestamps();$t->unique(['building_id','label']);
  });
  Schema::create('spad_building_charges',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->unsignedBigInteger('unit_id')->index();$t->string('title',170);
   $t->unsignedBigInteger('amount_toman');$t->date('due_date')->nullable();
   $t->string('status',20)->default('unpaid');$t->timestamp('paid_at')->nullable();
   $t->unsignedBigInteger('recorded_by')->nullable();$t->timestamps();
  });
  Schema::create('spad_building_expenses',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->string('title',170);$t->unsignedBigInteger('amount_toman');
   $t->date('expense_date');$t->text('description')->nullable();$t->timestamps();
  });
  Schema::create('spad_building_notices',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->string('title',170);$t->text('body');$t->timestamps();
  });
  Schema::create('spad_building_tickets',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('building_id')->index();
   $t->unsignedBigInteger('user_id')->index();$t->unsignedBigInteger('unit_id')->nullable();
   $t->string('subject',150);$t->text('message');
   $t->string('status',24)->default('open');$t->text('manager_reply')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_builder_profiles',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('user_id')->unique();
   $t->string('name',180);$t->string('city',110);$t->string('headline',220)->nullable();
   $t->text('biography')->nullable();$t->unsignedSmallInteger('experience_years')->default(0);
   $t->unsignedSmallInteger('completed_projects')->default(0);
   $t->string('logo_url',600)->nullable();$t->boolean('verified')->default(false);
   $t->boolean('published')->default(false);$t->unsignedSmallInteger('rank_priority')->default(1000);
   $t->timestamps();
  });
  Schema::create('spad_builder_projects',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('builder_id')->index();
   $t->string('title',180);$t->string('city',110);
   $t->text('description')->nullable();$t->string('image_url',600)->nullable();
   $t->unsignedSmallInteger('completed_year')->nullable();$t->timestamps();
  });
  Schema::create('spad_property_auctions',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('owner_user_id')->index();
   $t->string('title',220);$t->string('city',110);$t->string('district',180)->nullable();
   $t->text('description');$t->string('image_url',600)->nullable();
   $t->unsignedBigInteger('opening_bid_toman');$t->unsignedBigInteger('minimum_increment_toman');
   $t->timestamp('starts_at')->nullable();$t->timestamp('ends_at')->nullable();
   $t->string('status',24)->default('review')->index();
   $t->unsignedBigInteger('provisional_winner_user_id')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_auction_bids',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('auction_id')->index();
   $t->unsignedBigInteger('user_id')->index();$t->unsignedBigInteger('amount_toman');
   $t->timestamps();$t->index(['auction_id','amount_toman']);
  });
  DB::table('spad_community_settings')->insert([
   'id'=>1,'buildings_enabled'=>true,'builders_enabled'=>true,'auctions_enabled'=>true,
   'minimum_bid_increment'=>10000000,
   'auction_notice'=>'ثبت پیشنهاد به معنای انتقال مالکیت یا برنده قطعی نیست. انجام معامله منوط به بررسی مدارک، احراز هویت و قرارداد قانونی جداگانه است.',
   'created_at'=>now(),'updated_at'=>now()
  ]);
 }
 public function down() {
  foreach(['spad_auction_bids','spad_property_auctions','spad_builder_projects','spad_builder_profiles',
   'spad_building_tickets','spad_building_notices','spad_building_expenses','spad_building_charges',
   'spad_building_units','spad_buildings','spad_community_settings'] as $name) Schema::dropIfExists($name);
 }
}
