<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central server-side policy for KYC levels. Public UI is a hint only.
 * The only user-selectable special scopes are protected by actual endpoints.
 * Never store video, images, card serial or birth date in this class.
 */
class SpadBiometricAccess
{
    public const DEFAULT_SPECIAL = ['property-auctions','raffles'];

    private function config(): array
    {
        if (!Schema::hasTable('spad_web_service_connections')) return [];
        $row=DB::table('spad_web_service_connections')->where('code','api-ir')->first();
        $value=$row ? json_decode((string)$row->config_public,true) : [];
        return is_array($value) ? $value : [];
    }

    public function catalog(): array
    {
        $items=[
            ['code'=>'property-auctions','title'=>'مزایده ملکی','group'=>'ویژه'],
            ['code'=>'raffles','title'=>'قرعه‌کشی ملک','group'=>'ویژه'],
        ];
        if (Schema::hasTable('inquiry_services')) {
            foreach (DB::table('inquiry_services')->orderBy('title')
                ->get(['slug','title']) as $item) {
                $items[]=['code'=>'inquiries:'.$item->slug,
                    'title'=>'استعلام: '.$item->title,'group'=>'استعلامات'];
            }
        }
        return $items;
    }

    public function specialServices(): array
    {
        $config=$this->config();
        $selected=$config['biometric_special_services']??self::DEFAULT_SPECIAL;
        if (!is_array($selected)) $selected=self::DEFAULT_SPECIAL;
        $allowed=array_column($this->catalog(),'code');
        return array_values(array_unique(array_intersect($allowed,$selected)));
    }

    public function requiredFor(string $service): bool
    {
        return in_array($service,$this->specialServices(),true);
    }

    public function enabled(): bool
    {
        return app(ApiIrAccountServices::class)->enabled('video_live');
    }

    private function base($user)
    {
        if (!$user || !Schema::hasTable('spad_user_verifications')) return null;
        $v=DB::table('spad_user_verifications')->where('user_id',$user->id)->first();
        if (!$v || $v->status!=='approved' || (int)$v->level<1 ||
            $v->verification_provider!=='api_ir_shahkarpro' ||
            !$v->phone_verified || $v->shahkar_status!=='matched' ||
            $v->phone!==(string)$user->mobile || !$v->national_code_hash) return null;
        return $v;
    }

    public function verified($user): bool
    {
        $base=$this->base($user);
        if (!$base || !Schema::hasTable('spad_biometric_verifications')) return false;
        $row=DB::table('spad_biometric_verifications')
            ->where('user_id',$user->id)->first();
        if (!$row || $row->status!=='matched' || !$row->verified_at ||
            !$row->expires_at || now()->greaterThanOrEqualTo($row->expires_at))
            return false;
        $expectedPhone=hash_hmac('sha256',(string)$user->mobile,config('app.key'));
        return hash_equals((string)$base->national_code_hash,(string)$row->base_national_hash) &&
            hash_equals($expectedPhone,(string)$row->phone_hash);
    }

    public function status($user): array
    {
        $base=$this->base($user);
        $verified=$this->verified($user);
        $row=$user && Schema::hasTable('spad_biometric_verifications')
            ? DB::table('spad_biometric_verifications')->where('user_id',$user->id)->first() : null;
        return [
            'base_verified'=>(bool)$base,
            'level'=>$verified?2:($base?1:0),
            'biometric_verified'=>$verified,
            'biometric_enabled'=>$this->enabled(),
            'biometric_status'=>$verified?'approved':($row ? (string)$row->status : 'not_checked'),
            'biometric_expires_at'=>$verified?(string)$row->expires_at:null,
            'special_services'=>$this->specialServices(),
            'available_special_services'=>$this->catalog(),
        ];
    }

    public function assertFor($user,string $scope): void
    {
        if (!$this->requiredFor($scope)) return;
        abort_unless($this->base($user),403,
            'برای استفاده از این خدمت ابتدا احراز هویت پایه اسپاد را تکمیل کنید.');
        abort_unless($this->verified($user),403,
            'برای استفاده از این خدمت احراز ویدئویی بایومتریک سطح دو الزامی است. از پنل کاربری اقدام کنید.');
    }
}
