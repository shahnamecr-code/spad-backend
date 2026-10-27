<?php

namespace App\Http\Controllers;

use App\Models\InquiryRequest;
use App\Models\InquiryService;
use App\Models\InquirySetting;
use App\Models\WalletGateway;
use App\Services\InquiryExecutor;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class InquiryController extends Controller
{
    protected $wallets;
    protected $executor;

    public function __construct(WalletService $wallets, InquiryExecutor $executor)
    {
        $this->wallets = $wallets;
        $this->executor = $executor;
    }

    public function index(Request $request)
    {
        $query = InquiryService::query()->where('enabled', true);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->boolean('featured')) $query->where('featured', true);

        $services = $query->orderByDesc('featured')->orderBy('sort_order')->orderBy('id')->get()
            ->map(function ($service) { return $service->publicPayload(); });

        $categories = InquiryService::query()->where('enabled', true)->whereNotNull('category')
            ->where('category', '<>', '')->distinct()->orderBy('category')->pluck('category')->values();

        return response()->json([
            'data' => $services,
            'categories' => $categories,
            'settings' => $this->publicSettings(),
        ]);
    }

    public function show(InquiryService $service)
    {
        abort_unless($service->enabled, 404);
        return response()->json([
            'data' => $service->publicPayload(),
            'settings' => $this->publicSettings(),
        ]);
    }

    public function store(Request $request, InquiryService $service)
    {
        abort_unless($service->enabled, 404);
        if (InquirySetting::valueOf('inquiries_enabled', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'ثبت استعلام در حال حاضر غیرفعال است.'], 422);
        }

        $user = $request->user();
        $inputData = $service->normalizeSubmittedInputs((array) $request->input('inputs', []));
        [$rules, $attributes] = $this->inputRules($service);
        $inputValidator = Validator::make($inputData, $rules, [], $attributes);
        if ($inputValidator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $inputValidator->errors()->first(),
                'errors' => $inputValidator->errors()->toArray(),
            ], 422);
        }
        $validatedInputs = $inputValidator->validated();

        $paymentMethod = $service->price > 0 ? $request->input('payment_method') : 'free';
        if ($service->price > 0) {
            validator($request->all(), [
                'payment_method' => ['required', Rule::in(['wallet', 'gateway'])],
                'gateway_id' => ['nullable', 'integer', 'exists:wallet_gateways,id'],
            ])->validate();
        }

        if ($paymentMethod === 'wallet' && InquirySetting::valueOf('wallet_payment_enabled', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'پرداخت از کیف پول برای استعلام‌ها غیرفعال است.'], 422);
        }
        if ($paymentMethod === 'gateway' && InquirySetting::valueOf('gateway_payment_enabled', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'پرداخت آنلاین برای استعلام‌ها غیرفعال است.'], 422);
        }

        $reference = 'INQ-' . now()->format('ymd') . '-' . strtoupper(Str::random(8));

        if ($paymentMethod === 'wallet') {
            try {
                $inquiry = DB::transaction(function () use ($user, $service, $validatedInputs, $reference) {
                    $inquiry = InquiryRequest::create([
                        'user_id' => $user->id,
                        'inquiry_service_id' => $service->id,
                        'reference' => $reference,
                        'amount' => (int) $service->price,
                        'partner_payout_amount' => $service->execution_type === 'manual' ? min((int) $service->partner_payout, (int) $service->price) : 0,
                        'payment_method' => 'wallet',
                        'payment_status' => 'pending',
                        'status' => 'pending_payment',
                        'input_data' => $validatedInputs,
                        'expires_at' => now()->addMinutes(30),
                    ]);

                    $transaction = $this->wallets->debit($user, $service->price, [
                        'type' => 'purchase',
                        'reference' => 'inquiry-payment:' . $inquiry->id,
                        'source_type' => InquiryRequest::class,
                        'source_id' => $inquiry->id,
                        'title' => 'پرداخت هزینه استعلام',
                        'description' => 'پرداخت استعلام «' . $service->title . '» از کیف پول',
                        'meta' => ['inquiry_reference' => $inquiry->reference],
                    ]);

                    $inquiry->update([
                        'payment_status' => 'paid',
                        'wallet_transaction_id' => $transaction->id,
                        'paid_at' => now(),
                    ]);
                    $this->executor->log($inquiry, 'paid_wallet', 'هزینه استعلام از کیف پول پرداخت شد.');

                    // Manual/partner inquiries are entirely database-driven. Keep payment,
                    // assignment and queueing in ONE transaction. Any assignment/schema error
                    // therefore rolls the wallet debit back automatically.
                    if ($service->execution_type === 'manual') {
                        return $this->executor->process($inquiry->fresh(['service']));
                    }

                    return $inquiry->fresh(['service']);
                });
            } catch (\Throwable $e) {
                Log::error('Inquiry wallet transaction rolled back before completion.', [
                    'user_id' => optional($user)->id,
                    'service_id' => $service->id,
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'ثبت استعلام کامل نشد و مبلغی از کیف پول شما کسر نشده است. لطفاً دوباره تلاش کنید.',
                ], 422);
            }

            // API execution is intentionally outside the DB transaction because it may call
            // a remote service. Unexpected server-side crashes are compensated automatically.
            if ($service->execution_type !== 'manual') {
                try {
                    $inquiry = $this->executor->process($inquiry);
                } catch (\Throwable $e) {
                    Log::error('Inquiry processing crashed after wallet payment.', [
                        'inquiry_id' => $inquiry->id,
                        'reference' => $inquiry->reference,
                        'error' => $e->getMessage(),
                    ]);
                    $this->refundFailedWalletInquiry($inquiry, $user, $e->getMessage());

                    return response()->json([
                        'status' => false,
                        'message' => 'اجرای استعلام با خطای سیستمی مواجه شد و مبلغ به کیف پول شما بازگردانده شد.',
                    ], 422);
                }
            }

            return response()->json(['status' => true, 'data' => $this->safeRequestPayload($inquiry)]);
        }

        if ($paymentMethod === 'gateway') {
            $gatewayId = $request->input('gateway_id');
            $gateway = WalletGateway::query()->where('enabled', true)
                ->when($gatewayId, function ($q) use ($gatewayId) { $q->whereKey($gatewayId); })
                ->orderByDesc('is_default')->first();
            if (!$gateway) {
                return response()->json(['status' => false, 'message' => 'درگاه پرداخت فعالی یافت نشد.'], 422);
            }

            $config = $gateway->secureConfig();
            $currency = strtoupper(isset($config['currency']) ? $config['currency'] : 'T');
            $amount = (int) $service->price;
            $gatewayAmount = $currency === 'R' ? $amount * 10 : $amount;

            $inquiry = InquiryRequest::create([
                'user_id' => $user->id,
                'inquiry_service_id' => $service->id,
                'reference' => $reference,
                'amount' => $amount,
                'partner_payout_amount' => $service->execution_type === 'manual' ? min((int) $service->partner_payout, $amount) : 0,
                'payment_method' => 'gateway',
                'payment_status' => 'pending',
                'status' => 'pending_payment',
                'gateway_id' => $gateway->id,
                'gateway_amount' => $gatewayAmount,
                'gateway_currency' => $currency,
                'input_data' => $validatedInputs,
                'callback_token' => Str::random(64),
                'expires_at' => now()->addMinutes(30),
            ]);

            try {
                $invoice = (new Invoice)->amount($gatewayAmount)
                    ->detail('inquiry', $inquiry->reference)
                    ->detail('service', $service->title)
                    ->detail('user_id', $user->id);

                $callbackUrl = url('/api/inquiry-payments/callback/' . $inquiry->id . '/' . $inquiry->callback_token);
                $payment = Payment::via($gateway->driver)
                    ->config($gateway->secureConfig())
                    ->callbackUrl($callbackUrl)
                    ->purchase($invoice, function ($driver, $transactionId) use ($inquiry) {
                        $inquiry->transaction_id = (string) $transactionId;
                        $inquiry->save();
                    })->pay();

                $this->executor->log($inquiry, 'gateway_started', 'درخواست پرداخت آنلاین ایجاد شد.');
                return response()->json([
                    'status' => true,
                    'data' => $this->requestPayload($inquiry),
                    'payment' => $payment->jsonSerialize(),
                ]);
            } catch (\Throwable $e) {
                $inquiry->update(['payment_status' => 'failed', 'failure_message' => $e->getMessage()]);
                return response()->json(['status' => false, 'message' => 'اتصال به درگاه پرداخت انجام نشد. لطفاً دوباره تلاش کنید.'], 422);
            }
        }

        $inquiry = InquiryRequest::create([
            'user_id' => $user->id,
            'inquiry_service_id' => $service->id,
            'reference' => $reference,
            'amount' => 0,
            'partner_payout_amount' => 0,
            'payment_method' => 'free',
            'payment_status' => 'paid',
            'status' => 'processing',
            'input_data' => $validatedInputs,
            'paid_at' => now(),
        ]);
        try {
            $inquiry = $this->executor->process($inquiry);
        } catch (\Throwable $e) {
            Log::error('Free inquiry processing failed.', ['inquiry_id' => $inquiry->id, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'ثبت استعلام انجام شد اما ارجاع آن با خطا مواجه شد. لطفاً با پشتیبانی تماس بگیرید.'], 422);
        }
        return response()->json(['status' => true, 'data' => $this->safeRequestPayload($inquiry)]);
    }

    protected function refundFailedWalletInquiry(InquiryRequest $inquiry, $user, $reason)
    {
        try {
            DB::transaction(function () use ($inquiry, $user, $reason) {
                $locked = InquiryRequest::query()->lockForUpdate()->find($inquiry->id);
                if (!$locked || $locked->payment_status !== 'paid' || (int) $locked->amount <= 0) return;

                $refund = $this->wallets->credit($user, (int) $locked->amount, [
                    'type' => 'refund',
                    'reference' => 'inquiry-refund:' . $locked->id,
                    'source_type' => InquiryRequest::class,
                    'source_id' => $locked->id,
                    'title' => 'بازگشت هزینه استعلام',
                    'description' => 'بازگشت خودکار هزینه استعلام «' . optional($locked->service)->title . '» به علت خطای سیستمی',
                    'meta' => ['inquiry_reference' => $locked->reference],
                ]);

                $locked->update([
                    'payment_status' => 'refunded',
                    'status' => 'failed',
                    'failure_message' => mb_substr((string) $reason, 0, 1000),
                ]);
                $this->executor->log($locked, 'wallet_refunded_system_error', 'مبلغ به علت خطای سیستمی به کیف پول بازگردانده شد.', [
                    'refund_transaction_id' => $refund->id,
                ]);
            });
        } catch (\Throwable $refundError) {
            Log::critical('Automatic inquiry wallet refund failed.', [
                'inquiry_id' => $inquiry->id,
                'reference' => $inquiry->reference,
                'error' => $refundError->getMessage(),
            ]);
        }
    }

    protected function safeRequestPayload(InquiryRequest $inquiry)
    {
        try {
            return $this->requestPayload($inquiry);
        } catch (\Throwable $e) {
            Log::error('Inquiry response serialization failed.', [
                'inquiry_id' => $inquiry->id,
                'reference' => $inquiry->reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'id' => $inquiry->id,
                'reference' => $inquiry->reference,
                'amount' => (int) $inquiry->amount,
                'payment_status' => $inquiry->payment_status,
                'status' => $inquiry->status,
            ];
        }
    }

    public function myRequests(Request $request)
    {
        $items = InquiryRequest::query()->where('user_id', $request->user()->id)
            ->with('service')
            ->latest()->paginate(min(50, max(5, (int) $request->get('per_page', 15))));
        $items->getCollection()->transform(function ($item) { return $this->requestPayload($item); });
        return $items;
    }

    public function requestStatus(Request $request, $reference)
    {
        $inquiry = InquiryRequest::query()->where('reference', $reference)
            ->where('user_id', $request->user()->id)->with(['service', 'gateway:id,title,driver'])->firstOrFail();
        return response()->json(['data' => $this->requestPayload($inquiry, true)]);
    }

    public function correctInputs(Request $request, $reference)
    {
        $inquiry = InquiryRequest::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->with('service')
            ->firstOrFail();

        if ($inquiry->payment_status !== 'paid' || $inquiry->status !== 'needs_correction') {
            return response()->json(['status' => false, 'message' => 'این درخواست در وضعیت اصلاح اطلاعات قرار ندارد.'], 422);
        }

        $inputData = $inquiry->service->normalizeSubmittedInputs((array) $request->input('inputs', []));
        [$rules, $attributes] = $this->inputRules($inquiry->service);
        $validator = Validator::make($inputData, $rules, [], $attributes);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $inquiry = DB::transaction(function () use ($inquiry, $validator) {
            $locked = InquiryRequest::query()->lockForUpdate()->with('service')->findOrFail($inquiry->id);
            if ($locked->status !== 'needs_correction') return $locked;

            $locked->update([
                'input_data' => $validator->validated(),
                'status' => 'waiting_operator',
                'partner_accepted_at' => null,
                'correction_resubmitted_at' => now(),
                'failure_message' => null,
            ]);
            $this->executor->log($locked, 'customer_corrected_inputs', 'کاربر اطلاعات درخواست را اصلاح و مجدداً ارسال کرد.', [
                'correction_note' => $locked->correction_note,
            ]);
            return $this->executor->process($locked->fresh(['service']));
        });

        return response()->json([
            'status' => true,
            'message' => 'اطلاعات اصلاح شد و درخواست مجدداً برای همکار ارسال گردید.',
            'data' => $this->requestPayload($inquiry, true),
        ]);
    }

    public function downloadResultFile(Request $request, $reference)
    {
        $inquiry = InquiryRequest::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$inquiry->result_file_path || !Storage::disk('local')->exists($inquiry->result_file_path)) {
            abort(404, 'فایل نتیجه استعلام یافت نشد.');
        }

        return Storage::disk('local')->download(
            $inquiry->result_file_path,
            $inquiry->result_file_name ?: ('SPAD-' . $inquiry->reference),
            ['Content-Type' => $inquiry->result_file_mime ?: 'application/octet-stream']
        );
    }

    public function paymentOptions(Request $request)
    {
        $wallet = $this->wallets->walletFor($request->user());
        return response()->json(['data' => [
            'wallet' => [
                'balance' => (int) $wallet->balance,
                'status' => $wallet->status,
                'enabled' => InquirySetting::valueOf('wallet_payment_enabled', '1') === '1',
            ],
            'gateway_enabled' => InquirySetting::valueOf('gateway_payment_enabled', '1') === '1',
            'gateways' => WalletGateway::query()->where('enabled', true)->orderByDesc('is_default')->orderBy('id')
                ->get(['id', 'driver', 'title', 'is_default']),
        ]]);
    }

    protected function inputRules(InquiryService $service)
    {
        $rules = [];
        $attributes = [];

        foreach ($service->normalizedFormSchema() as $field) {
            $key = $field['key'];
            $fieldRules = [];
            $fieldRules[] = !empty($field['required']) ? 'required' : 'nullable';
            $type = $field['type'] ?? 'text';

            if ($type === 'number') $fieldRules[] = 'numeric';
            elseif ($type === 'email') $fieldRules[] = 'email';
            elseif ($type === 'mobile') $fieldRules[] = 'regex:/^09[0-9]{9}$/';
            elseif ($type === 'national_code') $fieldRules[] = 'regex:/^[0-9]{10}$/';
            elseif ($type === 'date') $fieldRules[] = 'date';
            else $fieldRules[] = 'string';

            $fieldRules[] = 'max:1000';
            if ($type === 'select' && !empty($field['options']) && is_array($field['options'])) {
                $fieldRules[] = Rule::in(array_values($field['options']));
            }

            $rules[$key] = $fieldRules;
            $attributes[$key] = $field['label'] ?: 'فیلد استعلام';
        }

        return [$rules, $attributes];
    }

    protected function requestPayload(InquiryRequest $item, $withResult = false)
    {
        $item->loadMissing('service', 'gateway:id,title,driver');
        $statuses = [
            'pending_payment' => 'در انتظار پرداخت', 'waiting_operator' => 'در صف بررسی',
            'processing' => 'در حال پردازش', 'needs_correction' => 'نیاز به اصلاح اطلاعات', 'completed' => 'تکمیل شده', 'failed' => 'ناموفق',
            'rejected' => 'رد شده', 'cancelled' => 'لغو شده',
        ];
        if ($item->status === 'waiting_operator') {
            $statuses['waiting_operator'] = $item->service && $item->service->partner_handler_type
                ? 'ارجاع شده به همکار تخصصی'
                : 'در صف بررسی اپراتور';
        }

        $data = [
            'id' => $item->id,
            'reference' => $item->reference,
            'service' => $item->service ? $item->service->publicPayload() : null,
            'amount' => (int) $item->amount,
            'payment_method' => $item->payment_method,
            'payment_status' => $item->payment_status,
            'status' => $item->status,
            'status_text' => $statuses[$item->status] ?? $item->status,
            'input_data' => $item->input_data ?: [],
            'input_fields' => $this->inputFieldPayload($item),
            'correction_note' => $item->correction_note,
            'correction_requested_at' => optional($item->correction_requested_at)->toIso8601String(),
            'correction_resubmitted_at' => optional($item->correction_resubmitted_at)->toIso8601String(),
            'has_result_file' => (bool) $item->result_file_path,
            'result_file_name' => $item->result_file_name,
            'result_file_mime' => $item->result_file_mime,
            'result_file_size' => $item->result_file_size ? (int) $item->result_file_size : null,
            'gateway' => $item->gateway,
            'gateway_reference_id' => $item->gateway_reference_id,
            'failure_message' => $item->failure_message,
            'created_at' => optional($item->created_at)->toIso8601String(),
            'paid_at' => optional($item->paid_at)->toIso8601String(),
            'completed_at' => optional($item->completed_at)->toIso8601String(),
        ];
        if ($withResult || $item->status === 'completed') {
            $data['result_text'] = $item->result_text;
            $data['result_data'] = $item->result_data ?: [];
        }
        return $data;
    }

    protected function inputFieldPayload(InquiryRequest $item)
    {
        $item->loadMissing('service');
        $inputs = (array) ($item->input_data ?: []);
        $labels = [];
        if ($item->service) {
            foreach ($item->service->normalizedFormSchema() as $field) {
                $labels[(string) $field['key']] = (string) ($field['label'] ?: $field['key']);
                $legacy = (string) ($field['_legacy_key'] ?? '');
                if ($legacy !== '') $labels[$legacy] = (string) ($field['label'] ?: $field['key']);
            }
        }

        $rows = [];
        foreach ($inputs as $key => $value) {
            $rows[] = [
                'key' => (string) $key,
                'label' => $labels[(string) $key] ?? (string) $key,
                'value' => $value,
            ];
        }
        return $rows;
    }

    protected function publicSettings()
    {
        return [
            'enabled' => InquirySetting::valueOf('inquiries_enabled', '1') === '1',
            'title' => InquirySetting::valueOf('page_title', 'استعلامات آنلاین SPAD'),
            'subtitle' => InquirySetting::valueOf('page_subtitle', 'استعلام‌های ملکی و ثبتی را سریع، امن و یکپارچه انجام دهید.'),
            'support_text' => InquirySetting::valueOf('support_text', 'در استعلام‌های دستی، نتیجه پس از بررسی اپراتور در پنل کاربری شما قرار می‌گیرد.'),
        ];
    }
}
