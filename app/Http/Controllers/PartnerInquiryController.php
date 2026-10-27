<?php

namespace App\Http\Controllers;

use App\Models\InquiryRequest;
use App\Models\PartnerPanel;
use App\Services\InquiryExecutor;
use App\Services\PartnerWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerInquiryController extends Controller
{
    protected $executor;
    protected $partnerWallets;

    public function __construct(InquiryExecutor $executor, PartnerWalletService $partnerWallets)
    {
        $this->executor = $executor;
        $this->partnerWallets = $partnerWallets;
    }

    protected function panel(Request $request, PartnerPanel $partnerPanel)
    {
        $resolved = $request->attributes->get('partner_panel');
        if (!$resolved || (int) $resolved->id !== (int) $partnerPanel->id) {
            abort(403, 'دسترسی به این پنل همکاری مجاز نیست.');
        }

        if (!in_array($resolved->type, [
            PartnerPanel::TYPE_NOTARY_OFFICE,
            PartnerPanel::TYPE_ENGINEERING_ORGANIZATION,
        ], true)) {
            abort(403, 'این بخش برای نوع پنل شما فعال نیست.');
        }

        return $resolved;
    }

    protected function assertRuntimeSchema()
    {
        $missing = [];
        if (!Schema::hasTable('inquiry_services') || !Schema::hasColumn('inquiry_services', 'partner_handler_type')) {
            $missing[] = 'inquiry_services.partner_handler_type';
        }
        if (!Schema::hasColumn('inquiry_services', 'partner_payout')) {
            $missing[] = 'inquiry_services.partner_payout';
        }
        if (!Schema::hasTable('inquiry_requests')) {
            $missing[] = 'inquiry_requests';
        } else {
            foreach ([
                'assigned_partner_panel_id', 'partner_assigned_at', 'partner_accepted_at', 'partner_note',
                'partner_payout_amount', 'partner_payout_transaction_id', 'result_file_path', 'result_file_name',
                'result_file_mime', 'result_file_size', 'correction_note', 'correction_requested_at', 'correction_resubmitted_at',
            ] as $column) {
                if (!Schema::hasColumn('inquiry_requests', $column)) $missing[] = 'inquiry_requests.' . $column;
            }
        }
        if (!Schema::hasTable('partner_wallets')) $missing[] = 'partner_wallets';
        if (!Schema::hasTable('partner_wallet_transactions')) $missing[] = 'partner_wallet_transactions';

        if ($missing) {
            throw new \RuntimeException('ساختار پنل استعلام همکار کامل نیست: ' . implode(', ', $missing));
        }
    }

    protected function runtimeError(\Throwable $e, $action, $panelId = null)
    {
        Log::error('Partner inquiry runtime error.', [
            'action' => $action,
            'partner_panel_id' => $panelId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'اطلاعات پنل همکاری موقتاً قابل دریافت نیست. ساختار استعلامات را به‌روزرسانی کنید و دوباره تلاش کنید.',
        ], 503);
    }

    public function dashboard(Request $request, PartnerPanel $partnerPanel)
    {
        $panel = $this->panel($request, $partnerPanel);
        try {
            $this->assertRuntimeSchema();
            $base = InquiryRequest::query()->where('assigned_partner_panel_id', $panel->id);

            return response()->json([
                'status' => true,
                'data' => [
                    'stats' => [
                        'new' => (clone $base)->where('status', 'waiting_operator')->count(),
                        'processing' => (clone $base)->where('status', 'processing')->count(),
                        'needs_correction' => (clone $base)->where('status', 'needs_correction')->count(),
                        'completed' => (clone $base)->where('status', 'completed')->count(),
                        'today_completed' => (clone $base)->where('status', 'completed')->whereDate('completed_at', today())->count(),
                    ],
                    'wallet' => $this->partnerWallets->summary($panel),
                    'latest' => (clone $base)
                        ->with(['service'])
                        ->latest()->limit(6)->get()->map(function ($item) {
                            return $this->payload($item);
                        })->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->runtimeError($e, 'dashboard', $panel->id);
        }
    }

    public function index(Request $request, PartnerPanel $partnerPanel)
    {
        $panel = $this->panel($request, $partnerPanel);
        try {
            $this->assertRuntimeSchema();
            $query = InquiryRequest::query()
                ->where('assigned_partner_panel_id', $panel->id)
                ->with(['service'])
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('service', function ($s) use ($search) {
                            $s->where('title', 'like', "%{$search}%");
                        });
                });
            }

            $result = $query->paginate(min(50, max(5, (int) $request->input('per_page', 15))));
            $result->getCollection()->transform(function ($item) {
                return $this->payload($item);
            });

            return $result;
        } catch (\Throwable $e) {
            return $this->runtimeError($e, 'index', $panel->id);
        }
    }

    public function show(Request $request, PartnerPanel $partnerPanel, InquiryRequest $inquiry)
    {
        $panel = $this->panel($request, $partnerPanel);
        try { $this->assertRuntimeSchema(); } catch (\Throwable $e) { return $this->runtimeError($e, 'detail', $panel->id); }
        $this->ensureAssigned($inquiry, $panel);
        $inquiry->loadMissing(['service', 'user:id,first_name,last_name,mobile']);

        return response()->json(['status' => true, 'data' => $this->payload($inquiry, true)]);
    }

    public function accept(Request $request, PartnerPanel $partnerPanel, InquiryRequest $inquiry)
    {
        $panel = $this->panel($request, $partnerPanel);
        try { $this->assertRuntimeSchema(); } catch (\Throwable $e) { return $this->runtimeError($e, 'accept', $panel->id); }
        $this->ensureAssigned($inquiry, $panel);

        if ($inquiry->payment_status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'پرداخت این سفارش تکمیل نشده است.'], 422);
        }

        if ($inquiry->status === 'completed') {
            return response()->json(['status' => true, 'message' => 'این سفارش قبلاً تکمیل شده است.']);
        }
        if (!in_array($inquiry->status, ['waiting_operator', 'processing'], true)) {
            return response()->json(['status' => false, 'message' => 'این سفارش در وضعیت قابل انجام نیست.'], 422);
        }

        $inquiry->update([
            'status' => 'processing',
            'partner_accepted_at' => $inquiry->partner_accepted_at ?: now(),
        ]);

        $this->executor->log($inquiry, 'partner_accepted', 'سفارش توسط همکار پذیرفته و وارد مرحله انجام شد.', [
            'partner_panel_id' => $panel->id,
        ]);

        return response()->json(['status' => true, 'message' => 'سفارش در حال انجام قرار گرفت.']);
    }

    public function requestCorrection(Request $request, PartnerPanel $partnerPanel, InquiryRequest $inquiry)
    {
        $panel = $this->panel($request, $partnerPanel);
        try { $this->assertRuntimeSchema(); } catch (\Throwable $e) { return $this->runtimeError($e, 'request-correction', $panel->id); }
        $this->ensureAssigned($inquiry, $panel);

        $data = $request->validate([
            'correction_note' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        if ($inquiry->payment_status !== 'paid' || !in_array($inquiry->status, ['waiting_operator', 'processing'], true)) {
            return response()->json(['status' => false, 'message' => 'این سفارش در وضعیت قابل ارجاع برای اصلاح نیست.'], 422);
        }

        $inquiry->update([
            'status' => 'needs_correction',
            'correction_note' => $data['correction_note'],
            'correction_requested_at' => now(),
            'correction_resubmitted_at' => null,
        ]);

        $this->executor->log($inquiry, 'partner_requested_correction', 'همکار برای ادامه استعلام درخواست اصلاح اطلاعات کاربر را ثبت کرد.', [
            'partner_panel_id' => $panel->id,
            'correction_note' => $data['correction_note'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'درخواست اصلاح اطلاعات برای کاربر ارسال شد.',
            'data' => $this->payload($inquiry->fresh(['service', 'user']), true),
        ]);
    }

    public function complete(Request $request, PartnerPanel $partnerPanel, InquiryRequest $inquiry)
    {
        $panel = $this->panel($request, $partnerPanel);
        try { $this->assertRuntimeSchema(); } catch (\Throwable $e) { return $this->runtimeError($e, 'complete', $panel->id); }
        $this->ensureAssigned($inquiry, $panel);

        $data = $request->validate([
            'result_text' => ['nullable', 'string', 'max:20000', 'required_without:result_file'],
            'result_data' => ['nullable', 'array'],
            'partner_note' => ['nullable', 'string', 'max:5000'],
            'result_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480', 'required_without:result_text'],
        ]);

        if ($inquiry->payment_status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'پرداخت این سفارش تکمیل نشده است.'], 422);
        }
        if (!in_array($inquiry->status, ['waiting_operator', 'processing'], true)) {
            return response()->json(['status' => false, 'message' => 'این سفارش در وضعیت قابل تکمیل نیست.'], 422);
        }

        $storedPath = null;
        $originalName = null;
        $mime = null;
        $size = null;
        if ($request->hasFile('result_file')) {
            $file = $request->file('result_file');
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $storedName = Str::uuid()->toString() . ($extension ? '.' . $extension : '');
            $storedPath = $file->storeAs('inquiries/results/' . $inquiry->id, $storedName, 'local');
            if (!$storedPath) {
                return response()->json([
                    'status' => false,
                    'message' => 'آپلود فایل نتیجه انجام نشد. لطفاً فایل را دوباره انتخاب و ارسال کنید.',
                ], 422);
            }
            $originalName = mb_substr((string) $file->getClientOriginalName(), 0, 255);
            $mime = mb_substr((string) ($file->getMimeType() ?: 'application/octet-stream'), 0, 120);
            $size = (int) $file->getSize();
        }

        try {
            $completed = DB::transaction(function () use ($inquiry, $panel, $data, $storedPath, $originalName, $mime, $size) {
                $locked = InquiryRequest::query()->lockForUpdate()->with(['service', 'user'])->findOrFail($inquiry->id);
                $this->ensureAssigned($locked, $panel);

                if ($locked->payment_status !== 'paid' || !in_array($locked->status, ['waiting_operator', 'processing'], true)) {
                    throw new \RuntimeException('این سفارش دیگر در وضعیت قابل تکمیل نیست.');
                }

                $locked->update([
                    'status' => 'completed',
                    'result_text' => trim((string) ($data['result_text'] ?? '')) ?: null,
                    'result_data' => $data['result_data'] ?? [],
                    'result_file_path' => $storedPath ?: $locked->result_file_path,
                    'result_file_name' => $storedPath ? $originalName : $locked->result_file_name,
                    'result_file_mime' => $storedPath ? $mime : $locked->result_file_mime,
                    'result_file_size' => $storedPath ? $size : $locked->result_file_size,
                    'partner_note' => $data['partner_note'] ?? null,
                    'failure_message' => null,
                    'partner_accepted_at' => $locked->partner_accepted_at ?: now(),
                    'completed_at' => now(),
                ]);

                $payoutAmount = min(
                    max(0, (int) ($locked->partner_payout_amount ?: optional($locked->service)->partner_payout ?: 0)),
                    max(0, (int) $locked->amount)
                );
                $transaction = $this->partnerWallets->creditInquiry($panel, $locked, $payoutAmount);
                if ($transaction) {
                    $locked->update(['partner_payout_transaction_id' => $transaction->id]);
                }

                $this->executor->log($locked, 'partner_completed', 'نتیجه سفارش توسط همکار ثبت شد و برای کاربر قابل مشاهده است.', [
                    'partner_panel_id' => $panel->id,
                    'partner_payout_amount' => $payoutAmount,
                    'result_file' => (bool) $storedPath,
                ]);

                return $locked->fresh(['service', 'user']);
            });
        } catch (\Throwable $e) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            Log::error('Partner inquiry completion failed.', [
                'inquiry_id' => $inquiry->id,
                'partner_panel_id' => $panel->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => false, 'message' => 'ثبت نتیجه کامل نشد و هیچ دستمزدی به کیف پول اضافه نشد. لطفاً دوباره تلاش کنید.'], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'نتیجه ثبت شد و دستمزد این سفارش به کیف پول پنل همکاری اضافه گردید.',
            'data' => $this->payload($completed, true),
        ]);
    }

    protected function ensureAssigned(InquiryRequest $inquiry, PartnerPanel $panel)
    {
        abort_unless((int) $inquiry->assigned_partner_panel_id === (int) $panel->id, 404);
        $inquiry->loadMissing('service');
        abort_unless(optional($inquiry->service)->partner_handler_type === $panel->type, 403);
    }

    protected function payload(InquiryRequest $item, $detail = false)
    {
        $item->loadMissing('service');
        $statusText = [
            'waiting_operator' => 'سفارش جدید',
            'processing' => 'در حال انجام',
            'needs_correction' => 'منتظر اصلاح کاربر',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'rejected' => 'رد شده',
            'cancelled' => 'لغو شده',
            'pending_payment' => 'در انتظار پرداخت',
        ];

        $earning = min(
            max(0, (int) ($item->partner_payout_amount ?: optional($item->service)->partner_payout ?: 0)),
            max(0, (int) $item->amount)
        );

        $data = [
            'id' => $item->id,
            'reference' => $item->reference,
            'service' => $item->service ? [
                'id' => $item->service->id,
                'title' => $item->service->title,
                'category' => $item->service->category,
                'icon' => $item->service->icon,
                'estimated_minutes' => $item->service->estimated_minutes,
            ] : null,
            'status' => $item->status,
            'status_text' => $statusText[$item->status] ?? $item->status,
            'amount' => (int) $item->amount,
            'earning_amount' => $earning,
            'input_data' => $item->input_data ?: [],
            'input_fields' => $this->inputFieldPayload($item),
            'correction_note' => $item->correction_note,
            'correction_requested_at' => optional($item->correction_requested_at)->toIso8601String(),
            'correction_resubmitted_at' => optional($item->correction_resubmitted_at)->toIso8601String(),
            'has_result_file' => (bool) $item->result_file_path,
            'result_file_name' => $item->result_file_name,
            'result_file_size' => $item->result_file_size ? (int) $item->result_file_size : null,
            'created_at' => optional($item->created_at)->toIso8601String(),
            'partner_assigned_at' => optional($item->partner_assigned_at)->toIso8601String(),
            'partner_accepted_at' => optional($item->partner_accepted_at)->toIso8601String(),
            'completed_at' => optional($item->completed_at)->toIso8601String(),
        ];

        if ($detail) {
            $item->loadMissing('user:id,first_name,last_name,mobile');
            $data['customer'] = [
                'name' => trim((string) optional($item->user)->first_name . ' ' . (string) optional($item->user)->last_name),
                'mobile' => optional($item->user)->mobile,
            ];
            $data['result_text'] = $item->result_text;
            $data['result_data'] = $item->result_data ?: [];
            $data['partner_note'] = $item->partner_note;
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
                $label = (string) ($field['label'] ?: $field['key']);
                $labels[(string) $field['key']] = $label;
                $legacy = (string) ($field['_legacy_key'] ?? '');
                if ($legacy !== '') $labels[$legacy] = $label;
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
}
