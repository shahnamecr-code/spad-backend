<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExtendSpadCommunityAdminProfiles extends Migration {
 public function up() {
  Schema::table('spad_builder_profiles',function(Blueprint $t){
   $t->unsignedBigInteger('user_id')->nullable()->change();
   $t->string('activity_area',250)->nullable();
   $t->text('building_names')->nullable();
  });
  Schema::table('spad_property_auctions',function(Blueprint $t){
   $t->unsignedBigInteger('owner_user_id')->nullable()->change();
   $t->unsignedBigInteger('advertisement_id')->nullable()->index();
  });
  // Fictional sample portfolios stay unpublished, unverified and clearly labelled.
  foreach([
   ['name'=>'سازنده نمونه یک (آزمایشی)','city'=>'شیراز','activity_area'=>'معالی‌آباد و فرهنگ‌شهر','experience_years'=>12,'building_names'=>'پروژه آزمایشی آفتاب، پروژه آزمایشی سپهر'],
   ['name'=>'سازنده نمونه دو (آزمایشی)','city'=>'تهران','activity_area'=>'سعادت‌آباد','experience_years'=>9,'building_names'=>'پروژه آزمایشی سرو'],
   ['name'=>'سازنده نمونه سه (آزمایشی)','city'=>'اصفهان','activity_area'=>'مرداویج','experience_years'=>16,'building_names'=>'پروژه آزمایشی نقش‌جهان']
  ] as $example){
   $id=DB::table('spad_builder_profiles')->insertGetId(array_merge($example,[
    'user_id'=>null,'headline'=>'پروفایل نمایشی جهت تست پنل مدیریت',
    'biography'=>'این رزومه آزمایشی است و به شخص یا پروژه واقعی اشاره ندارد.',
    'completed_projects'=>0,'published'=>false,'verified'=>false,'rank_priority'=>1000,
    'created_at'=>now(),'updated_at'=>now()
   ]));
  }
 }
 public function down() {
  DB::table('spad_builder_profiles')->whereNull('user_id')->where('headline','پروفایل نمایشی جهت تست پنل مدیریت')->delete();
  Schema::table('spad_property_auctions',function(Blueprint $t){$t->dropColumn('advertisement_id');});
  Schema::table('spad_builder_profiles',function(Blueprint $t){$t->dropColumn(['activity_area','building_names']);});
 }
}
