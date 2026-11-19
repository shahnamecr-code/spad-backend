<?php

namespace App\Http\Controllers;

use App\Models\PartnerPanel;
use App\Models\User;
use App\Services\SpadLoginOtp;
use App\Services\SpadLoginRateLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PartnerAuthController extends Controller
{
    private function findPartnerUser(string $mobile, ?string $panelType = null): ?User
    {
        return User::query()
            ->where('mobile', fix_mobile($mobile))
            ->whereHas('partnerPanels', function ($query) use ($panelType) {
                $query->where('status', PartnerPanel::STATUS_ACTIVE);
                if ($panelType) {
                    $query->where('type', $panelType);
                }
            })
            ->first();
    }

    private function panelTypeRules(): array
    {
        return ['nullable', Rule::in(array_keys(PartnerPanel::types()))];
    }

    private function missingPanelMessage(?string $panelType): string
    {
        if (!$panelType) {
            return 'پنل همکاری فعالی برای این شماره تعریف نشده است.';
        }

        $label = PartnerPanel::types()[$panelType] ?? 'انتخاب‌شده';
        return 'برای این شماره، پنل ' . $label . ' فعال نیست.';
    }

    private function userPayload(User $user): array
    {
        $payload = $user->toArray();
        $payload['partner_panels'] = $user->partnerPanels()
            ->active()
            ->orderBy('id')
            ->get()
            ->map(function (PartnerPanel $panel) {
                return $panel->publicPayload();
            })
            ->values()
            ->all();
        $payload['has_partner_panel'] = count($payload['partner_panels']) > 0;

        return $payload;
    }

    private function success(User $user, ?string $panelType = null)
    {
        $token = $user->createToken('partner-token')->plainTextToken;
        $selectedPanel = null;

        if ($panelType) {
            $selectedPanel = $user->partnerPanels()
                ->active()
                ->where('type', $panelType)
                ->orderBy('id')
                ->first();
        }

        return response()->json([
            'status' => true,
            'message' => 'ورود به پنل همکاری با موفقیت انجام شد',
            'token' => $token,
            'user' => $this->userPayload($user),
            'login_context' => 'partner',
            'selected_panel_id' => optional($selectedPanel)->id,
        ]);
    }

    public function sendLoginCode(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required', 'string', 'max:20'],
            'panel_type' => $this->panelTypeRules(),
        ]);

        $panelType = $data['panel_type'] ?? null;
        $user = $this->findPartnerUser($data['mobile'], $panelType);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => $this->missingPanelMessage($panelType),
            ]);
        }

        app(SpadLoginOtp::class)->send(fix_mobile($data['mobile']), $request);
        return response()->json([
            'status' => true, 'message' => 'کد ورود پنل همکاری ارسال شد.',
            'resend_after' => SpadLoginOtp::RESEND_SECONDS,
        ]);
    }

    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required', 'string', 'max:20'],
            'code' => ['required'],
            'panel_type' => $this->panelTypeRules(),
        ]);

        $mobile = fix_mobile($data['mobile']);
        $panelType = $data['panel_type'] ?? null;
        $user = $this->findPartnerUser($mobile, $panelType);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => $this->missingPanelMessage($panelType),
            ]);
        }

        if (!is_scalar($data['code']) || !app(SpadLoginOtp::class)->consume($mobile, (string) $data['code'], $request))
            return response()->json(['status'=>false,'code'=>'otp_invalid','message'=>'کد ورود نامعتبر یا منقضی است.'],422);
        if(is_null($user->phone_verified_at))$user->update(['phone_verified_at'=>now()]);
        return $this->success($user->fresh(), $panelType);
    }

    public function loginWithPassword(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'panel_type' => $this->panelTypeRules(),
        ]);

        $panelType = $data['panel_type'] ?? null;
        $mobile = fix_mobile($data['mobile']);
        $passwordAttempt = SpadLoginRateLimits::attempt('password', $mobile);
        $user = $this->findPartnerUser($mobile, $panelType);
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات ورود پنل همکاری نامعتبر است.',
            ]);
        }
        SpadLoginRateLimits::clear($passwordAttempt);

        if (is_null($user->phone_verified_at)) {
            return response()->json([
                'status' => false,
                'requires_verification' => true,
                'message' => 'شماره همراه این حساب هنوز تأیید نشده است. برای ادامه کد تأیید دریافت کنید.',
            ]);
        }

        return $this->success($user, $panelType);
    }
}
