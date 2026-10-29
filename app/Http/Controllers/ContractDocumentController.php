<?php

namespace App\Http\Controllers;

use App\Models\InquiryRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractDocumentController extends Controller
{
    private const SLUGS = ['online-agreement-request', 'tracking-code-request'];

    protected function customerRequest(Request $request, string $reference): InquiryRequest
    {
        $item = InquiryRequest::with('service')->where('reference', $reference)
            ->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($item->service && in_array($item->service->slug, self::SLUGS, true), 404);
        return $item;
    }

    private function rows(int $inquiryId)
    {
        return DB::table('inquiry_contract_documents')->where('inquiry_request_id', $inquiryId)
            ->orderBy('id')->get(['id', 'original_name', 'mime', 'size', 'created_at'])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->original_name,
                    'mime' => $row->mime,
                    'size' => (int) $row->size,
                    'created_at' => $row->created_at,
                ];
            })->values();
    }

    public function index(Request $request, string $reference)
    {
        $item = $this->customerRequest($request, $reference);
        return response()->json(['data' => $this->rows($item->id)]);
    }

    public function store(Request $request, string $reference)
    {
        $item = $this->customerRequest($request, $reference);
        abort_if(in_array($item->status, ['rejected', 'cancelled', 'completed'], true), 422, 'امکان بارگذاری مدرک برای این وضعیت وجود ندارد.');

        $data = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        $existing = DB::table('inquiry_contract_documents')
            ->where('inquiry_request_id', $item->id)->count();
        if ($existing + count($request->file('documents')) > 10) {
            return response()->json(['message' => 'حداکثر ده مدرک برای هر درخواست قابل ثبت است.'], 422);
        }

        $uploaded = [];
        try {
            foreach ($request->file('documents') as $file) {
                $name = Str::random(36) . '.' . $file->extension();
                // Local/private disk: never expose national IDs, deeds or contracts under public/.
                $path = $file->storeAs('inquiry-contract-documents/' . $item->id, $name, 'local');
                if (!$path) throw new \RuntimeException('Could not store document');
                $uploaded[] = $path;
                DB::table('inquiry_contract_documents')->insert([
                    'inquiry_request_id' => $item->id,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 190),
                    'disk_path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            foreach ($uploaded as $path) Storage::disk('local')->delete($path);
            DB::table('inquiry_contract_documents')->where('inquiry_request_id', $item->id)
                ->whereIn('disk_path', $uploaded)->delete();
            throw $e;
        }

        return response()->json([
            'message' => 'مدارک با موفقیت به پرونده درخواست اضافه شد.',
            'data' => $this->rows($item->id),
        ]);
    }

    public function adminIndex(int $inquiry)
    {
        $item = InquiryRequest::with('service')->findOrFail($inquiry);
        abort_unless($item->service && in_array($item->service->slug, self::SLUGS, true), 404);
        return response()->json(['data' => $this->rows($item->id)]);
    }

    public function adminDownload(int $inquiry, int $document)
    {
        $item = InquiryRequest::with('service')->findOrFail($inquiry);
        abort_unless($item->service && in_array($item->service->slug, self::SLUGS, true), 404);
        $row = DB::table('inquiry_contract_documents')
            ->where('inquiry_request_id', $item->id)->where('id', $document)->first();
        abort_unless($row && Storage::disk('local')->exists($row->disk_path), 404);
        return Storage::disk('local')->download($row->disk_path, $row->original_name, [
            'Content-Type' => $row->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
