<?php
namespace App\Services;

/**
 * Canonical API.ir form contracts, from API.ir's public official PHP SDK.
 * Read-only schemas shared by the customer UI, server validation and admin.
 * Updating a custom form in the DB must never drop a provider-required field.
 */
final class ApiIrInquirySchema
{
    private static function field(string $key, string $label, string $type='text',
        string $placeholder='',string $help=''): array
    {
        return ['key'=>$key,'label'=>$label,'type'=>$type,'required'=>true,
            'placeholder'=>$placeholder,'help'=>$help,'options'=>[]];
    }

    private static function select(string $key,string $label,array $choices,
        string $help=''): array
    {
        $f=self::field($key,$label,'select','','');
        $f['options']=$choices;
        $f['help']=$help;
        return $f;
    }

    public static function all(): array
    {
        $national=self::field('nationalCode','کد ملی صاحب حساب احراز‌شده',
            'national_code','کد ملی ۱۰ رقمی','صرفاً اطلاعات هویتی خودتان');
        $consent=self::select('consent','تأیید رضایت صاحب اطلاعات',
            ['بله، اطلاعات خودم است'],'درخواست فقط با رضایت صریح و برای اطلاعات خودتان انجام می‌شود.');
        $postal=self::field('postalCode','کد پستی ۱۰ رقمی ملک',
            'text','۱۲۳۴۵۶۷۸۹۰','بدون خط تیره یا فاصله');
        $bill=self::field('billID','شناسه قبض',
            'text','شناسه درج‌شده روی قبض','فقط شناسه قبض، نه شناسه پرداخت');
        $iban=self::field('iban','شماره شبا به نام خودتان','text',
            'IR000000000000000000000000','حروف IR و ۲۴ رقم، بدون فاصله');
        $birth=self::field('birthDate','تاریخ تولد شمسی',
            'text','1370/1/1','مانند 1370/1/1 — تاریخ صاحب کد ملی');
        $party=self::select('isCompany','نوع متقاضی',
            ['شخص حقیقی','شخص حقوقی'],
            'استعلام شخص حقوقی به احراز اختیار نمایندگی نیاز دارد؛ فعلاً فقط شخص حقیقی مجاز است.');
        $cheque=self::field('chequeID','شناسه چک صیادی',
            'text','شناسه ۱۶ رقمی صیادی','شناسه یکتای مندرج روی چک صیادی');
        $chequeConsent=self::select('consent','تأیید مجوز بررسی چک',
            ['بله، مجوز بررسی چک را دارم'],
            'صرفاً برای چکی که حق قانونی بررسی آن را دارید.');
        return [
            'property-apiir-water-bill'=>[$bill],
            'property-apiir-power-bill'=>[$bill],
            'property-apiir-gas-bill'=>[$bill],
            'property-apiir-active-loans'=>[$national,$consent],
            'property-apiir-unpaid-cheque-pro'=>[$national,$consent],
            'property-apiir-postal-code-location'=>[$postal],
            'property-apiir-postal-code-pro'=>[$postal],
            'property-apiir-cheque-color'=>[$party,$national,$consent],
            'property-apiir-cheque-info'=>[$cheque,$chequeConsent],
            'property-apiir-sana'=>[$party,$national,$consent],
            'property-apiir-iban-match'=>[$national,$birth,$iban,$consent],
            'property-apiir-iban-match-pro'=>[$national,$iban,$consent],
        ];
    }

    public static function for(string $slug): array
    {
        return self::all()[$slug]??[];
    }

    public static function normalize(string $slug,array $inputs): array
    {
        if (!isset(self::all()[$slug])) return $inputs;
        $keys=['nationalCode','postalCode','billID','chequeID','iban','birthDate'];
        $from=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $to=['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
        foreach ($keys as $key) {
            if (!isset($inputs[$key]) || !is_string($inputs[$key])) continue;
            $value=str_replace($from,$to,trim($inputs[$key]));
            if ($key==='iban') $value=strtoupper(preg_replace('/[\s\-]+/u','',$value));
            elseif ($key==='birthDate') $value=preg_replace('/[\s]+/u','',$value);
            else $value=preg_replace('/[\s\-]+/u','',$value);
            $inputs[$key]=$value;
        }
        return $inputs;
    }

    /**
     * Required provider JSON fields, excluding internal consent.
     * Used in zero-cost deployment checks; never contains PII.
     */
    public static function providerKeys(): array
    {
        return [
            'property-apiir-water-bill'=>['billID'],
            'property-apiir-power-bill'=>['billID'],
            'property-apiir-gas-bill'=>['billID'],
            'property-apiir-active-loans'=>['nationalCode'],
            'property-apiir-unpaid-cheque-pro'=>['nationalCode'],
            'property-apiir-postal-code-location'=>['postalCode'],
            'property-apiir-postal-code-pro'=>['postalCode'],
            'property-apiir-cheque-color'=>['nationalCode','isCompany'],
            'property-apiir-cheque-info'=>['chequeID'],
            'property-apiir-sana'=>['nationalCode','isCompany'],
            'property-apiir-iban-match'=>['nationalCode','birthDate','iban'],
            'property-apiir-iban-match-pro'=>['nationalCode','iban'],
        ];
    }
}
