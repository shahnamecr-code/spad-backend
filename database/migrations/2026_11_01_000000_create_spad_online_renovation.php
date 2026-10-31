<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateSpadOnlineRenovation extends Migration {
 public function up(){
  if(!Schema::hasTable('spad_renovation_projects')) Schema::create('spad_renovation_projects',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('user_id')->index();$t->unsignedBigInteger('advertisement_id')->nullable();
   $t->unsignedBigInteger('tour_id')->nullable();$t->string('source_type',25);
   $t->string('title',150);$t->unsignedInteger('area')->default(80);$t->string('status',25)->default('draft');
   $t->string('design_style',35)->default('modern');$t->json('plan')->nullable();
   $t->json('invoice')->nullable();$t->unsignedBigInteger('estimated_total_toman')->nullable();
   $t->unsignedInteger('estimated_days')->nullable();$t->unsignedBigInteger('admin_quote_toman')->nullable();
   $t->unsignedInteger('admin_days')->nullable();$t->text('admin_note')->nullable();
   $t->timestamps();$t->index(['user_id','status']);
  });
  if(!Schema::hasTable('spad_renovation_media')) Schema::create('spad_renovation_media',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('project_id')->index();$t->unsignedBigInteger('user_id');
   $t->string('kind',20);$t->string('room',100)->default('فضای اصلی');
   $t->string('path',380);$t->string('mime',70);$t->unsignedInteger('bytes');
   $t->timestamps();
  });
  if(!Schema::hasTable('spad_renovation_rates')) Schema::create('spad_renovation_rates',function(Blueprint $t){
   $t->bigIncrements('id');$t->string('code',40)->unique();$t->string('title',100);
   $t->string('unit',20);$t->unsignedBigInteger('price_toman');$t->unsignedSmallInteger('days');
   $t->boolean('enabled')->default(true);$t->timestamps();
  });
  $rates=[
    ['paint','رنگ‌آمیزی دیوار','متر مربع',350000,2],
    ['floor','تعویض کف‌پوش','متر مربع',1200000,3],
    ['cabinet','کابینت آشپزخانه','متر طول',9000000,5],
    ['lighting','نورپردازی و چراغ','عدد',2500000,1],
    ['bathroom','بازسازی سرویس بهداشتی','فضا',35000000,7],
    ['ceiling','سقف کاذب','متر مربع',850000,3]
  ];
  foreach($rates as $r)DB::table('spad_renovation_rates')->updateOrInsert(['code'=>$r[0]],[
   'title'=>$r[1],'unit'=>$r[2],'price_toman'=>$r[3],'days'=>$r[4],
   'enabled'=>true,'created_at'=>now(),'updated_at'=>now()
  ]);
 }
 public function down(){
  // Projects are user-owned records. Do not erase saved images/invoices on rollback.
 }
}
