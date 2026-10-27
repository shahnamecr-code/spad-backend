<?php

namespace App\Http\Controllers;

use App\Models\LoginRequest;
use App\Models\PartnerPanel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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
            'mobile' => ['required'],
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

        $mobile = fix_mobile($data['mobile']);
        $token = createToken(6);
        $loginRequest = LoginRequest::updateOrCreate(
            ['mobile' => $mobile],
            ['token' => $token]
        );

        if (!$loginRequest) {
            return response()->json([
                'status' => false,
                'message' => 'مشکلی در برقراری ارتباط پیش آمده است. کمی بعد دوباره امتحان کنید.',
            ]);
        }

        $executed = RateLimiter::attempt(
            'send-partner-message:' . $user->id,
            3,
            function () use ($mobile, $token) {
                sms()->send('hi', function ($sms) use ($mobile, $token) {
                    $sms->to([$mobile]);
                    $sms->template(config('sms.login_template'), [
                        'code' => $token,
                    ]);
                });
            }
        );

        if (!$executed) {
            return response()->json([
                'status' => false,
                'message' => 'تعداد درخواست کد ورود بیش از حد مجاز است.',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'کد ورود پنل همکاری برای شما ارسال شد.',
        ]);
    }

    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required'],
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

        $loginRequest = LoginRequest::query()
            ->where('token', $data['code'])
            ->where('mobile', $mobile)
            ->first();

        if (!$loginRequest) {
            return response()->json([
                'status' => false,
                'message' => 'کد وارد شده معتبر نیست.',
            ]);
        }

        if ($loginRequest->updated_at->addSeconds(120)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'کد وارد شده منقضی شده است.',
            ]);
        }

        if (is_null($user->phone_verified_at)) {
            $user->update(['phone_verified_at' => now()]);
        }

        $loginRequest->delete();

        return $this->success($user->fresh(), $panelType);
    }

    public function loginWithPassword(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required'],
            'password' => ['required'],
            'panel_type' => $this->panelTypeRules(),
        ]);

        $panelType = $data['panel_type'] ?? null;
        $user = $this->findPartnerUser($data['mobile'], $panelType);
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات ورود پنل همکاری نامعتبر است.',
            ]);
        }

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
