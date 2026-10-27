<?php

namespace App\Services;

use App\Models\InquiryRequest;
use App\Models\InquiryRequestLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InquiryExecutor
{
    protected $partnerAssignments;

    public function __construct(InquiryPartnerAssignmentService $partnerAssignments)
    {
        $this->partnerAssignments = $partnerAssignments;
    }

    public function process(InquiryRequest $request)
    {
        $request->loadMissing('service');
        $service = $request->service;

        if (!$service) {
            throw new RuntimeException('سرویس استعلام یافت نشد.');
        }

        if ($request->payment_status !== 'paid') {
            throw new RuntimeException('پرداخت درخواست استعلام تکمیل نشده است.');
        }

        if ($service->execution_type === 'manual') {
            if ($request->status !== 'completed') {
                if ($service->partner_handler_type) {
                    $panel = $this->partnerAssignments->assign($request);
                    if ($panel) {
                        $this->log($request, 'waiting_partner', 'درخواست برای انجام توسط همکار تخصصی در پنل همکاری قرار گرفت.', [
                            'partner_panel_id' => $panel->id,
                            'partner_type' => $panel->type,
                        ]);
                    }
                } else {
                    $request->update(['status' => 'waiting_operator']);
                    $this->log($request, 'waiting_operator', 'درخواست برای بررسی اپراتور در صف قرار گرفت.');
                }
            }
            return $request->fresh();
        }

        return $this->executeApi($request);
    }

    public function executeApi(InquiryRequest $request)
    {
        $request->loadMissing('service');
        $service = $request->service;
        $config = $service->secureApiConfig();

        $url = trim((string) Arr::get($config, 'url', ''));
        if (!$url) {
            $request->update([
                'status' => 'failed',
                'failure_message' => 'آدرس API برای این سرویس تنظیم نشده است.',
            ]);
            $this->log($request, 'api_failed', 'آدرس API تنظیم نشده است.');
            return $request->fresh();
        }

        $method = strtoupper((string) Arr::get($config, 'method', 'POST'));
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH'], true)) $method = 'POST';

        $headers = (array) Arr::get($config, 'headers', []);
        $staticPayload = (array) Arr::get($config, 'static_payload', []);
        $payload = array_merge((array) $request->input_data, $staticPayload);
        $timeout = max(5, min(90, (int) Arr::get($config, 'timeout', 25)));
        $resultPath = trim((string) Arr::get($config, 'result_path', ''));
        $successPath = trim((string) Arr::get($config, 'success_path', ''));
        $successValue = Arr::get($config, 'success_value', null);

        $request->update(['status' => 'processing', 'failure_message' => null]);
        $this->log($request, 'api_started', 'ارسال درخواست به سرویس API آغاز شد.');

        try {
            $client = Http::timeout($timeout)->acceptJson();
            if ($headers) $client = $client->withHeaders($headers);

            if ($method === 'GET') {
                $response = $client->get($url, $payload);
            } else {
                $response = $client->send($method, $url, ['json' => $payload]);
            }

            if (!$response->successful()) {
                throw new RuntimeException('پاسخ ناموفق از سرویس استعلام (HTTP ' . $response->status() . ').');
            }

            $json = $response->json();
            if (!is_array($json)) {
                $json = ['result' => $response->body()];
            }

            if ($successPath !== '') {
                $actual = data_get($json, $successPath);
                if ($successValue !== null && $successValue !== '') {
                    $expected = $this->normalizeExpectedValue($successValue);
                    if ($actual != $expected) {
                        throw new RuntimeException('سرویس استعلام نتیجه موفق برنگرداند.');
                    }
                }
            }

            $result = $resultPath !== '' ? data_get($json, $resultPath) : $json;
            if (is_null($result)) $result = $json;
            if (!is_array($result)) $result = ['result' => $result];

            $request->update([
                'status' => 'completed',
                'result_data' => $result,
                'result_text' => Arr::get($config, 'result_title', 'نتیجه استعلام با موفقیت دریافت شد.'),
                'failure_message' => null,
                'completed_at' => now(),
            ]);

            $this->log($request, 'api_completed', 'نتیجه استعلام آنلاین دریافت و ذخیره شد.');
            return $request->fresh();
        } catch (\Throwable $e) {
            $request->update([
                'status' => 'failed',
                'failure_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            $this->log($request, 'api_failed', mb_substr($e->getMessage(), 0, 500));
            return $request->fresh();
        }
    }

    protected function normalizeExpectedValue($value)
    {
        if (!is_string($value)) return $value;
        $trimmed = trim($value);
        $lower = strtolower($trimmed);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;
        if (is_numeric($trimmed)) return strpos($trimmed, '.') !== false ? (float) $trimmed : (int) $trimmed;
        return $trimmed;
    }

    public function log(InquiryRequest $request, $event, $message = null, array $payload = [])
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
