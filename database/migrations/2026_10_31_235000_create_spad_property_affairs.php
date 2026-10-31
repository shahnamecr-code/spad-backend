<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSpadPropertyAffairs extends Migration {
 public function up(){
  Schema::create('spad_property_affair_services',function(Blueprint $t){
   $t->bigIncrements('id');$t->string('slug',90)->unique();$t->string('title',170);
   $t->string('subtitle',300)->nullable();$t->string('category',70)->default('ثبت‌نام و خدمات');
   $t->string('icon',30)->default('home');$t->text('description')->nullable();
   $t->text('requirements')->nullable();$t->string('official_url',400)->nullable();
   $t->json('form_schema')->nullable();$t->unsignedBigInteger('price_toman')->default(0);
   $t->boolean('enabled')->default(true);$t->boolean('accepting_requests')->default(true);
   $t->boolean('featured')->default(false);$t->unsignedSmallInteger('sort_order')->default(100);
   $t->timestamps();
  });
  Schema::create('spad_property_affair_requests',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('service_id')->index();
   $t->unsignedBigInteger('user_id')->index();$t->string('tracking_code',36)->unique();
   $t->json('form_data');$t->string('status',25)->default('submitted')->index();
   $t->text('public_note')->nullable();$t->text('admin_note')->nullable();
   $t->unsignedBigInteger('quoted_price_toman')->nullable();$t->timestamp('completed_at')->nullable();
   $t->timestamps();
  });
  Schema::create('spad_property_affair_documents',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('request_id')->index();
   $t->unsignedBigInteger('user_id')->index();$t->string('file_path',500);
   $t->string('original_name',180);$t->string('mime',100);$t->unsignedBigInteger('file_size');
   $t->timestamps();
  });
  Schema::create('spad_property_affair_events',function(Blueprint $t){
   $t->bigIncrements('id');$t->unsignedBigInteger('request_id')->index();
   $t->string('status',25);$t->string('actor_type',15);
   $t->unsignedBigInteger('actor_id')->nullable();$t->text('note')->nullable();$t->timestamps();
  });
  $items=[
   ['amlak-housing','ثبت و اصلاح املاک و اسکان','اظهار مالکیت، محل اقامت و اصلاح اطلاعات سامانه ملی','ثبت‌نام و اسکان','home','راهنمایی و تشکیل پرونده برای خوداظهاری و رفع ابهام مشخصات ملک و سکونت.','کد پستی، نشانی، نقش مالک یا مستأجر؛ ورود و تأیید نهایی فقط در سامانه رسمی توسط صاحب حساب.','https://amlak.mrud.ir',true,10,['role','postal','city','description']],
   ['rental-deposit-loan','وام ودیعه مسکن مستأجران','بررسی مدارک و راهنمای ثبت تسهیلات کمک‌ودیعه','تسهیلات مسکن','wallet','بررسی اولیه قرارداد اجاره، شرایط و مدارک متقاضی و راهنمایی مسیر ثبت رسمی.','اجاره‌نامه و کد رهگیری معتبر، مشخصات سکونت؛ شرایط و سقف وام مطابق اطلاعیه روز.','https://saman.mrud.ir',true,20,['city','contract','description']],
   ['national-housing','نهضت ملی مسکن','بررسی شرایط، ظرفیت شهرها و پیگیری پرونده متقاضی','طرح‌های حمایتی','building','کمک به بررسی شرایط اولیه و پیگیری پرونده؛ ثبت رسمی فقط در شهرهای دارای ظرفیت ممکن است.','شهر مورد تقاضا، وضعیت مالکیت و شرایط اعلام‌شده وزارت راه و شهرسازی.','https://saman.mrud.ir',false,30,['city','description']],
   ['rental-contract','خودنویس و کد رهگیری اجاره','آماده‌سازی اطلاعات اجاره‌نامه و راهنمای ثبت قرارداد','قرارداد و رهگیری','file','بررسی اولیه مدارک موجر و مستأجر و راهنمای مسیر قانونی ثبت قرارداد و دریافت کد رهگیری.','نشانی، کد پستی، اطلاعات قرارداد؛ تأیید هویت و امضای نهایی در سامانه خودنویس انجام می‌شود.','https://khodnevis.mrud.ir',false,40,['role','postal','contract','description']],
   ['family-housing','طرح حمایت از خانواده و جوانی جمعیت','بررسی ضوابط و مدارک متقاضیان زمین یا مسکن حمایتی','طرح‌های حمایتی','users','بررسی اولیه شرایط استفاده از طرح‌های حمایتی خانواده؛ واگذاری و تأیید نهایی توسط مراجع مسئول انجام می‌شود.','شهر یا روستای متقاضی و مدارک احراز شرایط طبق آخرین دستورالعمل رسمی.','https://saman.mrud.ir',false,50,['city','description']],
   ['rural-land','ثبت و پیگیری واگذاری زمین روستایی','بررسی ظرفیت و مدارک طرح واگذاری اراضی روستایی','طرح‌های حمایتی','map','کمک به بررسی روستاهای دارای ظرفیت و آماده‌سازی پرونده در چارچوب اعلامیه‌های رسمی.','نام استان و روستای متقاضی، مدارک شرایط لازم و بررسی ظرفیت روز.','https://saman.mrud.ir',false,60,['city','description']],
   ['purchase-loan-file','آماده‌سازی پرونده وام خرید مسکن','راهنمای مدارک، اوراق و پیگیری درخواست تسهیلات خرید','تسهیلات مسکن','credit','بررسی اولیه اطلاعات ملک و قرارداد برای آماده‌سازی پرونده تسهیلات خرید مسکن.','موقعیت ملک، اطلاعات قرارداد و شرایط بانکی؛ نتیجه نهایی به بررسی بانک وابسته است.',null,false,70,['city','postal','description']]
  ];
  $f=[
   'role'=>['key'=>'role','label'=>'نقش شما در ملک','type'=>'select','required'=>true,'options'=>['مالک','مستأجر','نماینده قانونی']],
   'postal'=>['key'=>'postal_code','label'=>'کد پستی ملک','type'=>'text','required'=>true,'placeholder'=>'کد پستی ۱۰ رقمی'],
   'city'=>['key'=>'city','label'=>'شهر / روستای مورد نظر','type'=>'text','required'=>true,'placeholder'=>'مثلاً شیراز'],
   'contract'=>['key'=>'contract_number','label'=>'کد رهگیری قرارداد (در صورت وجود)','type'=>'text','required'=>false],
   'description'=>['key'=>'description','label'=>'شرح درخواست و وضعیت فعلی','type'=>'textarea','required'=>true,'placeholder'=>'شرح مختصر موضوع و نوع کمکی که نیاز دارید']
  ];
  foreach($items as $item){
   [$slug,$title,$subtitle,$category,$icon,$desc,$req,$url,$featured,$order,$fields]=$item;
   DB::table('spad_property_affair_services')->insert([
    'slug'=>$slug,'title'=>$title,'subtitle'=>$subtitle,'category'=>$category,'icon'=>$icon,
    'description'=>$desc,'requirements'=>$req,'official_url'=>$url,'featured'=>$featured,
    'form_schema'=>json_encode(array_map(function($key)use($f){return $f[$key];},$fields),JSON_UNESCAPED_UNICODE),
    'price_toman'=>0,'enabled'=>true,'accepting_requests'=>true,'sort_order'=>$order,
    'created_at'=>now(),'updated_at'=>now()
   ]);
  }
 }
 public function down(){
  Schema::dropIfExists('spad_property_affair_events');
  Schema::dropIfExists('spad_property_affair_documents');
  Schema::dropIfExists('spad_property_affair_requests');
  Schema::dropIfExists('spad_property_affair_services');
 }
}
