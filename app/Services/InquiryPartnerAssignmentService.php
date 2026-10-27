<?php

namespace App\Services;

use App\Models\InquiryPartnerRoutingRule;
use App\Models\InquiryRequest;
use App\Models\InquiryRequestLog;
use App\Models\PartnerPanel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class InquiryPartnerAssignmentService
{
    const ROUTING_TIMEZONE = 'Asia/Tehran';

    public function assign(InquiryRequest $request, PartnerPanel $preferred = null)
    {
        $this->assertRuntimeSchema();
        $request->loadMissing('service');
        $service = $request->service;
        $handlerType = optional($service)->partner_handler_type;

        if (!$service || $service->execution_type !== 'manual' || !$handlerType) {
            return null;
        }

        // Explicit admin selection always wins.
        if ($preferred) {
            $panel = $this->validPanel($preferred->id, $handlerType);
            if (!$panel) return null;
            return $this->applyAssignment($request, $panel, 'manual_admin');
        }

        // Keep an already-valid assignment stable on retries/status refreshes.
        if ($request->assigned_partner_panel_id) {
            $current = $this->validPanel($request->assigned_partner_panel_id, $handlerType);
            if ($current) return $current;
        }

        // Calendar: service-specific override first, then the global group roster.
        $scheduled = $this->scheduledPanel($service->id, $handlerType);
        if ($scheduled) {
            return $this->applyAssignment($request, $scheduled['panel'], $scheduled['source'], [
                'weekday' => $scheduled['weekday'],
                'rule_id' => $scheduled['rule_id'],
            ]);
        }

        // Safe fallback: least-loaded active panel in the same specialist group.
        $panel = PartnerPanel::query()
            ->active()
            ->where('type', $handlerType)
            ->whereHas('user', function ($q) {
                $q->where('status', User::APPROVED);
            })
            ->withCount(['assignedInquiryRequests as open_inquiries_count' => function ($q) {
                $q->whereIn('status', ['waiting_operator', 'processing']);
            }])
            ->orderBy('open_inquiries_count')
            ->orderBy('id')
            ->first();

        if (!$panel) {
            $request->update([
                'assigned_partner_panel_id' => null,
                'partner_assigned_at' => null,
                'partner_accepted_at' => null,
                'status' => 'waiting_operator',
            ]);
            $this->log($request, 'partner_waiting_assignment', 'همکار فعال برای این نوع استعلام پیدا نشد و درخواست در صف تخصیص باقی ماند.', [
                'partner_handler_type' => $handlerType,
                'assignment_source' => 'none',
            ]);
            return null;
        }

        return $this->applyAssignment($request, $panel, 'load_balance');
    }


    protected function assertRuntimeSchema()
    {
        $missing = [];
        if (!Schema::hasTable('inquiry_services') || !Schema::hasColumn('inquiry_services', 'partner_handler_type')) {
            $missing[] = 'inquiry_services.partner_handler_type';
        }
        if (!Schema::hasTable('inquiry_requests')) {
            $missing[] = 'inquiry_requests';
        } else {
            foreach (['assigned_partner_panel_id', 'partner_assigned_at', 'partner_accepted_at', 'partner_note'] as $column) {
                if (!Schema::hasColumn('inquiry_requests', $column)) $missing[] = 'inquiry_requests.' . $column;
            }
        }

        if ($missing) {
            throw new \RuntimeException('ساختار ارجاع استعلام کامل نیست: ' . implode(', ', $missing) . '. migration را اجرا کنید.');
        }
    }

    protected function scheduledPanel($serviceId, $handlerType)
    {
        if (!class_exists(InquiryPartnerRoutingRule::class) || !Schema::hasTable('inquiry_partner_routing_rules')) return null;

        $weekday = Carbon::now(self::ROUTING_TIMEZONE)->dayOfWeek;
        $specificScope = InquiryPartnerRoutingRule::scopeKey($serviceId);

        $rule = InquiryPartnerRoutingRule::query()
            ->enabled()
            ->where('partner_type', $handlerType)
            ->where('weekday', $weekday)
            ->where('scope_key', $specificScope)
            ->first();

        $source = 'calendar_service';
        if (!$rule) {
            $rule = InquiryPartnerRoutingRule::query()
                ->enabled()
                ->where('partner_type', $handlerType)
                ->where('weekday', $weekday)
                ->where('scope_key', 'all')
                ->first();
            $source = 'calendar_group';
        }

        if (!$rule) return null;

        $panel = $this->validPanel($rule->partner_panel_id, $handlerType);
        if (!$panel) return null;

        return [
            'panel' => $panel,
            'source' => $source,
            'weekday' => $weekday,
            'rule_id' => $rule->id,
        ];
    }

    protected function validPanel($panelId, $handlerType)
    {
        return PartnerPanel::query()
            ->active()
            ->whereKey($panelId)
            ->where('type', $handlerType)
            ->whereHas('user', function ($q) {
                $q->where('status', User::APPROVED);
            })
            ->first();
    }

    protected function applyAssignment(InquiryRequest $request, PartnerPanel $panel, $source, array $extra = [])
    {
        $request->update([
            'assigned_partner_panel_id' => $panel->id,
            'assigned_admin_id' => null,
            'partner_assigned_at' => now(),
            'partner_accepted_at' => null,
            'status' => 'waiting_operator',
        ]);

        $this->log($request, 'partner_assigned', 'درخواست به پنل همکاری ارجاع شد.', array_merge([
            'partner_panel_id' => $panel->id,
            'partner_type' => $panel->type,
            'assignment_source' => $source,
        ], $extra));

        return $panel;
    }

    protected function log(InquiryRequest $request, $event, $message, array $payload = [])
    {
        return InquiryRequestLog::create([
            'inquiry_request_id' => $request->id,
            'admin_id' => auth('admin')->check() ? auth('admin')->id() : null,
            'event' => $event,
            'message' => $message,
            'payload' => $payload ?: null,
        ]);
    }
}
