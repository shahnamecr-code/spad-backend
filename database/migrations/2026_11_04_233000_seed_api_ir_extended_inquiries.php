<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedApiIrExtendedInquiries extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services'))
            throw new RuntimeException('Inquiry service catalog must be installed first.');

        $consent=['key'=>'consent','label'=>'رضایت برای استعلام اطلاعات خودم','type'=>'select',
            'required'=>true,'options'=>['بله، اطلاعات خودم است']];
        $national=['key'=>'nationalCode','label'=>'کد ملی صاحب حساب احراز هویت‌شده',
            'type'=>'national_code','required'=>true,'placeholder'=>'۱۰ رقم کد ملی'];
        $iban=['key'=>'iban','label'=>'شماره شبای بانکی','type'=>'text','required'=>true,
            'placeholder'=>'IR000000000000000000000000',
            'help'=>'حروف IR و ۲۴ رقم، بدون فاصله'];
        $birth=['key'=>'birthDate','label'=>'تاریخ تولد شمسی','type'=>'text','required'=>true,
            'placeholder'=>'1375/1/1','help'=>'تاریخ تولد صاحب کد ملی'];
        $items=[
            ['cheque-color','استعلام رنگ چک صیادی','چک و اعتبار',
                'رنگ اعتباری صادرکننده چک صیادی مطابق API.ir',[$national,$consent]],
            ['cheque-info','استعلام مشخصات چک صیادی','چک و اعتبار',
                'مشخصات چک با شناسه صیادی و رضایت قانونی برای بررسی',[
                    ['key'=>'chequeID','label'=>'شناسه چک صیادی','type'=>'text',
                     'required'=>true,'placeholder'=>'شناسه ۱۵ یا ۱۶ رقمی چک'],
                    ['key'=>'consent','label'=>'تأیید داشتن مجوز بررسی چک','type'=>'select',
                     'required'=>true,'options'=>['بله، مجوز بررسی چک را دارم']],
                ]],
            ['sana','استعلام سامانه ثنا','هویت و ثبت',
                'وضعیت ثبت‌نام صاحب کد ملی در سامانه ثنا',[$national,$consent]],
            ['iban-match','تطبیق کد ملی با شبا','بانک و اعتبار',
                'تطبیق شماره شبا و کد ملی با تاریخ تولد شمسی',[$national,$birth,$iban,$consent]],
            ['iban-match-pro','تطبیق کد ملی با شبا پرو (سیاح)','بانک و اعتبار',
                'تطبیق کد ملی با شبا از سرویس سیاح بدون تاریخ تولد',[$national,$iban,$consent]],
        ];
        foreach ($items as $n=>$item) {
            [$name,$title,$category,$description,$schema]=$item;
            $slug='property-apiir-'.$name;
            if (DB::table('inquiry_services')->where('slug',$slug)->exists()) continue;
            DB::table('inquiry_services')->insert([
                'slug'=>$slug,'title'=>$title,'category'=>$category,
                'short_description'=>$description,
                'description'=>'استعلام آنلاین API.ir؛ پس از تأیید مجوز سرویس و تعیین تعرفه توسط مدیریت قابل خرید خواهد بود.',
                'accent'=>'#097bc6','price'=>0,'execution_type'=>'api',
                'estimated_minutes'=>3,'form_schema'=>json_encode($schema,JSON_UNESCAPED_UNICODE),
                'api_config_encrypted'=>null,'enabled'=>false,'featured'=>false,
                'sort_order'=>650+$n,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
    }

    public function down()
    {
        // Keep history, payment references and administrator-defined prices.
        // Deliberately no destructive rollback of seeded inquiry services.
    }
}
