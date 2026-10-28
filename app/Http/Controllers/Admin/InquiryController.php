<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InquiryRequest;
use App\Models\InquiryPartnerRoutingRule;
use App\Models\InquiryService;
use App\Models\InquirySetting;
use App\Models\PartnerPanel;
use App\Models\User;
use App\Services\InquiryExecutor;
use App\Services\InquiryPartnerAssignmentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    protected $executor;
    protected $wallets;
    protected $partnerAssignments;

    public function __construct(InquiryExecutor $executor, WalletService $wallets, InquiryPartnerAssignmentService $partnerAssignments)
    {
        $this->executor = $executor;
        $this->wallets = $wallets;
        $this->partnerAssignments = $partnerAssignments;
    }

    public function services(Request $request)
    {
        $query = InquiryService::query()->withCount('requests');
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if ($request->filled('execution_type')) $query->where('execution_type', $request->execution_type);
        return $query->orderBy('sort_order')->orderByDesc('id')->paginate(min(100, max(10, (int) $request->get('per_page', 30))));
    }

    public function service(InquiryService $service)
    {
        $this->ensureSuperAdmin();
        return response()->json(['data' => $this->adminServicePayload($service)]);
    }

    public function saveService(Request $request, InquiryService $service = null)
    {
        $this->ensureSuperAdmin();
        $service = $service ?: new InquiryService();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('inquiry_services', 'slug')->ignore($service->id)],
            'category' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:500'],
            'accent' => ['nullable', 'string', 'max:30'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'partner_payout' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'execution_type' => ['required', Rule::in(['api', 'manual'])],
            'partner_handler_type' => ['nullable', 'required_if:execution_type,manual', Rule::in(array_keys($this->partnerHandlerTypes()))],
            'estimated_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
            'form_schema' => ['nullable', 'array'],
            'form_schema.*.key' => ['required_with:form_schema', 'string', 'max:100', 'distinct', 'regex:/^[A-Za-z_][A-Za-z0-9_-]*$/'],
            'form_schema.*.label' => ['required_with:form_schema', 'string', 'max:190'],
            'form_schema.*.type' => ['required_with:form_schema', Rule::in(['text', 'number', 'email', 'mobile', 'national_code', 'date', 'textarea', 'select'])],
            'form_schema.*.required' => ['nullable', 'boolean'],
            'form_schema.*.placeholder' => ['nullable', 'string', 'max:300'],
            'form_schema.*.help' => ['nullable', 'string', 'max:500'],
            'form_schema.*.options' => ['nullable', 'array'],
            'api_config' => ['nullable', 'array'],
            'enabled' => ['required', 'boolean'],
            'featured' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
        ]);

        $partnerPayout = $data['execution_type'] === 'manual' ? (int) ($data['partner_payout'] ?? 0) : 0;
        if ($partnerPayout > (int) $data['price']) {
            return response()->json([
                'status' => false,
                'message' => 'دستمزد همکار نمی‌تواند بیشتر از مبلغی باشد که کاربر برای سرویس پرداخت می‌کند.',
                'errors' => ['partner_payout' => ['دستمزد همکار باید کمتر یا مساوی هزینه سرویس باشد.']],
            ], 422);
        }

        if ($data['execution_type'] === 'manual' && !Schema::hasColumn('inquiry_services', 'partner_payout')) {
            return response()->json([
                'status' => false,
                'message' => 'ساختار دستمزد همکار هنوز روی دیتابیس نصب نشده است. ابتدا php artisan migrate --force را اجرا کنید.',
            ], 409);
        }

        if ($data['execution_type'] === 'manual' && !Schema::hasColumn('inquiry_services', 'partner_handler_type')) {
            return response()->json([
                'status' => false,
                'message' => 'ساختار ارجاع اپراتوری هنوز روی دیتابیس نصب نشده است. ابتدا php artisan migrate --force را اجرا کنید.',
            ], 409);
        }

        $service->fill([
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']) . '-' . Str::lower(Str::random(4)),
            'category' => $data['category'] ?? null,
            'icon' => $data['icon'] ?? null,
            'accent' => $data['accent'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'partner_payout' => $partnerPayout,
            'execution_type' => $data['execution_type'],
            'partner_handler_type' => $data['execution_type'] === 'manual' ? $data['partner_handler_type'] : null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'form_schema' => array_values($data['form_schema'] ?? []),
            'enabled' => (bool) $data['enabled'],
            'featured' => (bool) $data['featured'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($data['execution_type'] === 'api') {
            $old = $service->exists ? $service->secureApiConfig() : [];
            $incoming = (array) ($data['api_config'] ?? []);
            $service->setApiConfig($this->mergeSecureConfig($old, $incoming));
        } else {
            $service->api_config_encrypted = null;
        }

        try {
            $service->save();
        } catch (\Throwable $e) {
            Log::error('Inquiry service save failed', [
                'service_id' => $service->id,
                'execution_type' => $data['execution_type'],
                'partner_handler_type' => $data['partner_handler_type'] ?? null,
                'partner_payout' => $partnerPayout,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'ذخیره سرویس انجام نشد. ساختار دیتابیس و تنظیمات ارجاع را بررسی کنید.',
            ], 422);
        }
        return response()->json(['status' => true, 'message' => 'سرویس استعلام ذخیره شد.', 'id' => $service->id]);
    }

    public function deleteService(InquiryService $service)
    {
        $this->ensureSuperAdmin();
        if ($service->requests()->exists()) {
            $service->update(['enabled' => false]);
            return response()->json(['status' => true, 'message' => 'به‌دلیل وجود سابقه، سرویس غیرفعال شد.']);
        }
        $service->delete();
        return response()->json(['status' => true, 'message' => 'سرویس استعلام حذف شد.']);
    }

    public function requests(Request $request)
    {
        $query = InquiryRequest::query()->with([
            'service:id,title,slug,execution_type,partner_handler_type',
            'user:id,first_name,last_name,mobile,email',
            'gateway:id,title,driver',
            'operator:id,first_name,last_name',
            'assignedPartner:id,type,title,organization_name,user_id',
        ]);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->filled('execution_type')) {
            $query->whereHas('service', function ($q) use ($request) { $q->where('execution_type', $request->execution_type); });
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('mobile', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function ($s) use ($search) { $s->where('title', 'like', "%{$search}%"); });
            });
        }
        return $query->latest()->paginate(min(100, max(10, (int) $request->get('per_page', 30))));
    }

    public function request(InquiryRequest $inquiry)
    {
        $inquiry->load([
            'service', 'user:id,first_name,last_name,mobile,email', 'gateway:id,title,driver',
            'operator:id,first_name,last_name', 'assignedPartner.user:id,first_name,last_name,mobile', 'logs' => function ($q) { $q->latest(); },
        ]);
        $partnerOptions = collect();
        if ($inquiry->service && $inquiry->service->partner_handler_type) {
            $partnerOptions = PartnerPanel::query()
                ->active()
                ->where('type', $inquiry->service->partner_handler_type)
                ->with('user:id,first_name,last_name,mobile')
                ->orderBy('organization_name')
                ->orderBy('id')
                ->get()
                ->map(function ($panel) {
                    return [
                        'id' => $panel->id,
                        'title' => $panel->title,
                        'organization_name' => $panel->organization_name,
                        'type_label' => $panel->type_label,
                        'user' => $panel->user,
                    ];
                })->values();
        }
        return response()->json(['data' => $inquiry, 'partner_options' => $partnerOptions]);
    }

    public function assignPartner(Request $request, InquiryRequest $inquiry)
    {
        if (!$inquiry->service || $inquiry->service->execution_type !== 'manual' || !$inquiry->service->partner_handler_type) {
            return response()->json(['status' => false, 'message' => 'این سرویس برای ارجاع به همکار تنظیم نشده است.'], 422);
        }
        if ($inquiry->payment_status !== 'paid' || in_array($inquiry->status, ['completed', 'rejected', 'cancelled'], true)) {
            return response()->json(['status' => false, 'message' => 'این سفارش در وضعیت قابل ارجاع نیست.'], 422);
        }

        $data = $request->validate([
            'partner_panel_id' => ['nullable', 'integer', Rule::exists('partner_panels', 'id')],
        ]);

        $panel = null;
        if (!empty($data['partner_panel_id'])) {
            $panel = PartnerPanel::query()->active()->whereKey($data['partner_panel_id'])
                ->where('type', $inquiry->service->partner_handler_type)->first();
            if (!$panel) {
                return response()->json(['status' => false, 'message' => 'همکار انتخاب‌شده فعال نیست یا با نوع این سرویس تطابق ندارد.'], 422);
            }
        }

        $assigned = $this->partnerAssignments->assign($inquiry, $panel);
        if (!$assigned) {
            return response()->json(['status' => false, 'message' => 'همکار فعالی برای این سرویس پیدا نشد.'], 422);
        }

        return response()->json(['status' => true, 'message' => 'سفارش به همکار انتخاب‌شده ارجاع شد.']);
    }

    public function assign(Request $request, InquiryRequest $inquiry)
    {
        $inquiry->update(['assigned_admin_id' => auth('admin')->id()]);
        $this->executor->log($inquiry, 'assigned', 'درخواست توسط اپراتور تحویل گرفته شد.');
        return response()->json(['status' => true, 'message' => 'درخواست به شما اختصاص یافت.']);
    }

    public function complete(Request $request, InquiryRequest $inquiry)
    {
        $data = $request->validate([
            'result_text' => ['nullable', 'string', 'max:20000'],
            'result_data' => ['nullable', 'array'],
            'operator_note' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($inquiry->payment_status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'پرداخت این درخواست تکمیل نشده است.'], 422);
        }
        $inquiry->update([
            'assigned_admin_id' => $inquiry->assigned_admin_id ?: auth('admin')->id(),
            'status' => 'completed',
            'result_text' => $data['result_text'] ?? 'استعلام توسط اپراتور تکمیل شد.',
            'result_data' => $data['result_data'] ?? [],
            'operator_note' => $data['operator_note'] ?? null,
            'failure_message' => null,
            'completed_at' => now(),
        ]);
        $this->executor->log($inquiry, 'manual_completed', 'نتیجه استعلام توسط اپراتور ثبت شد.');
        return response()->json(['status' => true, 'message' => 'نتیجه استعلام ثبت و برای کاربر قابل مشاهده شد.']);
    }

    public function reject(Request $request, InquiryRequest $inquiry)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $inquiry->update([
            'assigned_admin_id' => $inquiry->assigned_admin_id ?: auth('admin')->id(),
            'status' => 'rejected',
            'failure_message' => $data['reason'],
        ]);
        $this->executor->log($inquiry, 'rejected', $data['reason']);
        return response()->json(['status' => true, 'message' => 'درخواست رد شد.']);
    }

    public function retryApi(InquiryRequest $inquiry)
    {
        if (!$inquiry->service || $inquiry->service->execution_type !== 'api') {
            return response()->json(['status' => false, 'message' => 'این درخواست از نوع API نیست.'], 422);
        }
        if ($inquiry->payment_status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'پرداخت درخواست تکمیل نشده است.'], 422);
        }
        $result = $this->executor->executeApi($inquiry);
        return response()->json(['status' => $result->status === 'completed', 'message' => $result->status === 'completed' ? 'استعلام مجدد با موفقیت انجام شد.' : ($result->failure_message ?: 'استعلام ناموفق بود.')]);
    }

    public function refund(InquiryRequest $inquiry)
    {
        $this->ensureSuperAdmin();
        if ($inquiry->payment_method !== 'wallet') {
            return response()->json(['status' => false, 'message' => 'بازگشت خودکار در این نسخه فقط برای پرداخت کیف پول انجام می‌شود.'], 422);
        }
        if ($inquiry->payment_status === 'refunded') {
            return response()->json(['status' => true, 'message' => 'این پرداخت قبلاً به کیف پول بازگردانده شده است.']);
        }
        if ($inquiry->payment_status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'این درخواست پرداخت موفق ندارد.'], 422);
        }

        try {
            DB::transaction(function () use ($inquiry) {
                $locked = InquiryRequest::query()->whereKey($inquiry->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status === 'refunded') return;
                $this->wallets->credit($locked->user, $locked->amount, [
                    'type' => 'refund',
                    'reference' => 'inquiry-refund:' . $locked->id,
                    'source_type' => InquiryRequest::class,
                    'source_id' => $locked->id,
                    'title' => 'بازگشت هزینه استعلام',
                    'description' => 'بازگشت وجه استعلام ' . $locked->reference,
                    'admin_id' => auth('admin')->id(),
                    'allow_inactive' => true,
                ]);
                $locked->update(['payment_status' => 'refunded', 'status' => 'cancelled']);
                $this->executor->log($locked, 'refunded', 'هزینه استعلام به کیف پول کاربر بازگردانده شد.');
            });
            return response()->json(['status' => true, 'message' => 'مبلغ به کیف پول کاربر بازگردانده شد.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function routingCalendar()
    {
        $this->ensureSuperAdmin();

        $types = $this->partnerHandlerTypes();
        $partners = [];
        foreach ($types as $type => $label) {
            $partners[$type] = PartnerPanel::query()
                ->active()
                ->where('type', $type)
                ->whereHas('user', function ($q) { $q->where('status', User::APPROVED); })
                ->with('user:id,first_name,last_name,mobile,status')
                ->orderBy('organization_name')
                ->orderBy('title')
                ->orderBy('id')
                ->get()
                ->map(function ($panel) {
                    $fallbackName = trim(optional($panel->user)->first_name . ' ' . optional($panel->user)->last_name);
                    return [
                        'id' => $panel->id,
                        'title' => $panel->title,
                        'organization_name' => $panel->organization_name,
                        'display_name' => $panel->organization_name ?: ($panel->title ?: ($fallbackName ?: optional($panel->user)->mobile)),
                        'mobile' => optional($panel->user)->mobile,
                    ];
                })->values();
        }

        $rules = InquiryPartnerRoutingRule::query()
            ->with(['service:id,title,execution_type,partner_handler_type', 'partnerPanel:id,type,title,organization_name'])
            ->orderBy('partner_type')
            ->orderBy('weekday')
            ->orderBy('scope_key')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'partner_type' => $rule->partner_type,
                    'weekday' => (int) $rule->weekday,
                    'scope_key' => $rule->scope_key,
                    'inquiry_service_id' => $rule->inquiry_service_id,
                    'service_title' => optional($rule->service)->title,
                    'partner_panel_id' => $rule->partner_panel_id,
                    'partner_name' => optional($rule->partnerPanel)->organization_name ?: optional($rule->partnerPanel)->title,
                    'enabled' => (bool) $rule->enabled,
                ];
            })->values();

        $services = InquiryService::query()
            ->where('execution_type', 'manual')
            ->whereIn('partner_handler_type', array_keys($types))
            ->orderBy('title')
            ->get(['id', 'title', 'partner_handler_type'])
            ->map(function ($service) use ($types) {
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'partner_handler_type' => $service->partner_handler_type,
                    'partner_handler_label' => $types[$service->partner_handler_type] ?? $service->partner_handler_type,
                ];
            })->values();

        $days = collect(InquiryPartnerRoutingRule::dayLabels())->map(function ($label, $value) {
            return ['value' => (int) $value, 'label' => $label];
        })->values();

        return response()->json(['data' => [
            'timezone' => InquiryPartnerAssignmentService::ROUTING_TIMEZONE,
            'partner_types' => $types,
            'partners' => $partners,
            'days' => $days,
            'rules' => $rules,
            'services' => $services,
        ]]);
    }

    public function saveRoutingRule(Request $request)
    {
        $this->ensureSuperAdmin();
        $types = $this->partnerHandlerTypes();
        $data = $request->validate([
            'partner_type' => ['required', Rule::in(array_keys($types))],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'inquiry_service_id' => ['nullable', 'integer', Rule::exists('inquiry_services', 'id')],
            'partner_panel_id' => ['required', 'integer', Rule::exists('partner_panels', 'id')],
        ]);

        $panel = PartnerPanel::query()->active()
            ->whereKey($data['partner_panel_id'])
            ->where('type', $data['partner_type'])
            ->first();
        if (!$panel) {
            return response()->json(['status' => false, 'message' => 'پنل همکاری انتخاب‌شده فعال نیست یا با گروه انتخابی تطابق ندارد.'], 422);
        }

        $serviceId = $data['inquiry_service_id'] ?? null;
        if ($serviceId) {
            $service = InquiryService::query()->find($serviceId);
            if (!$service || $service->execution_type !== 'manual' || $service->partner_handler_type !== $data['partner_type']) {
                return response()->json(['status' => false, 'message' => 'سرویس انتخاب‌شده اپراتوری نیست یا به این گروه همکار تعلق ندارد.'], 422);
            }
        }

        $scopeKey = InquiryPartnerRoutingRule::scopeKey($serviceId);
        $rule = InquiryPartnerRoutingRule::query()->firstOrNew([
            'partner_type' => $data['partner_type'],
            'weekday' => (int) $data['weekday'],
            'scope_key' => $scopeKey,
        ]);
        if (!$rule->exists) $rule->created_by = auth('admin')->id();
        $rule->fill([
            'inquiry_service_id' => $serviceId,
            'partner_panel_id' => $panel->id,
            'enabled' => true,
            'updated_by' => auth('admin')->id(),
        ]);
        $rule->save();

        return response()->json(['status' => true, 'message' => 'برنامه ارجاع ذخیره شد.', 'id' => $rule->id]);
    }

    public function deleteRoutingRule(InquiryPartnerRoutingRule $rule)
    {
        $this->ensureSuperAdmin();
        $rule->delete();
        return response()->json(['status' => true, 'message' => 'برنامه ارجاع حذف شد.']);
    }

    public function settings()
    {
        $this->ensureSuperAdmin();
        return response()->json(['data' => [
            'inquiries_enabled' => InquirySetting::valueOf('inquiries_enabled', '1') === '1',
            'wallet_payment_enabled' => InquirySetting::valueOf('wallet_payment_enabled', '1') === '1',
            'gateway_payment_enabled' => InquirySetting::valueOf('gateway_payment_enabled', '1') === '1',
            'page_title' => InquirySetting::valueOf('page_title', 'استعلامات آنلاین SPAD'),
            'page_subtitle' => InquirySetting::valueOf('page_subtitle', 'استعلام‌های ملکی و ثبتی را سریع، امن و یکپارچه انجام دهید.'),
            'support_text' => InquirySetting::valueOf('support_text', 'در استعلام‌های دستی، نتیجه پس از بررسی اپراتور در پنل کاربری شما قرار می‌گیرد.'),
            'seven_inquiry_service_ids' => $this->sevenServiceIds(),
        ]]);
    }

    public function saveSettings(Request $request)
    {
        $this->ensureSuperAdmin();
        $data = $request->validate([
            'inquiries_enabled' => ['required', 'boolean'],
            'wallet_payment_enabled' => ['required', 'boolean'],
            'gateway_payment_enabled' => ['required', 'boolean'],
            'page_title' => ['nullable', 'string', 'max:190'],
            'page_subtitle' => ['nullable', 'string', 'max:1000'],
            'support_text' => ['nullable', 'string', 'max:2000'],
            'seven_inquiry_service_ids' => ['sometimes', 'array'],
            'seven_inquiry_service_ids.*' => ['integer', 'distinct', Rule::exists('inquiry_services', 'id')],
        ]);
        if (array_key_exists('seven_inquiry_service_ids', $data)) {
            $ids = array_map('intval', $data['seven_inquiry_service_ids']);
            if (count($ids) !== 0 && count($ids) !== 7) {
                return response()->json(['status' => false, 'message' => 'برای بسته هفت‌گانه دقیقاً ۷ سرویس انتخاب کنید.'], 422);
            }
            if ($ids && InquiryService::query()->whereIn('id', $ids)->where('enabled', true)->count() !== 7) {
                return response()->json(['status' => false, 'message' => 'تمام هفت سرویس انتخاب‌شده باید فعال باشند.'], 422);
            }
            $data['seven_inquiry_service_ids'] = json_encode($ids);
        }
        foreach ($data as $key => $value) InquirySetting::putValue($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        return response()->json(['status' => true, 'message' => 'تنظیمات استعلامات ذخیره شد.']);
    }

    protected function sevenServiceIds()
    {
        $ids = json_decode((string) InquirySetting::valueOf('seven_inquiry_service_ids', '[]'), true);
        if (!is_array($ids)) return [];
        $ids = array_values(array_filter($ids, function ($id) {
            return is_int($id) || ctype_digit((string) $id);
        }));
        return array_map('intval', $ids);
    }

    protected function ensureSuperAdmin()
    {
        $admin = auth('admin')->user();
        abort_unless($admin && method_exists($admin, 'hasRole') && $admin->hasRole('Super Admin'), 403, 'دسترسی به این بخش فقط برای مدیر کل مجاز است.');
    }

    protected function adminServicePayload(InquiryService $service)
    {
        $data = $service->toArray();
        $data['form_schema'] = array_map(function ($field) { unset($field['_legacy_key']); return $field; }, $service->normalizedFormSchema());
        $data['api_config'] = $service->maskedApiConfig();
        $types = $this->partnerHandlerTypes();
        $data['partner_handler_label'] = $service->partner_handler_type ? ($types[$service->partner_handler_type] ?? $service->partner_handler_type) : null;
        $data['partner_handler_types'] = $types;
        unset($data['api_config_encrypted']);
        return $data;
    }

    protected function partnerHandlerTypes()
    {
        return [
            PartnerPanel::TYPE_NOTARY_OFFICE => 'دفاتر اسناد رسمی',
            PartnerPanel::TYPE_ENGINEERING_ORGANIZATION => 'دفاتر نظام مهندسی',
        ];
    }

    protected function mergeSecureConfig(array $old, array $incoming)
    {
        $result = [];
        foreach ($incoming as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->mergeSecureConfig(
                    isset($old[$key]) && is_array($old[$key]) ? $old[$key] : [],
                    $value
                );
                continue;
            }

            // Values containing the mask are placeholders returned by the admin API.
            // Keep the real encrypted value, but allow empty/new values and deleted keys
            // to be intentionally changed by the administrator.
            if (is_string($value) && strpos($value, '••') !== false) {
                if (array_key_exists($key, $old)) $result[$key] = $old[$key];
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
