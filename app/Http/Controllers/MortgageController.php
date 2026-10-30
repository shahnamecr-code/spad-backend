<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MortgageController extends Controller
{
    private function settings()
    {
        $defaults = ['bond_price' => 119200, 'bond_updated_at' => '۲۷ مهر ۱۴۰۵', 'broker_enabled' => true];
        if (!Schema::hasTable('mortgage_settings')) return array_merge($defaults, ['broker_enabled' => false]);
        $row = DB::table('mortgage_settings')->where('id', 1)->first();
        if (!$row) return $defaults;
        return [
            'bond_price' => (int) $row->bond_price,
            'bond_updated_at' => $row->bond_updated_at,
            'broker_enabled' => (bool) $row->broker_enabled,
        ];
    }

    public function publicSettings()
    {
        return response()->json(['data' => $this->settings()]);
    }

    public function store(Request $request)
    {
        $settings = $this->settings();
        abort_unless($settings['broker_enabled'], 422, 'ثبت درخواست کارپرداز غیرفعال است.');
        $data = $request->validate([
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'city' => ['required', 'in:tehran,province,other'],
            'household' => ['required', 'in:individual,couple'],
            'purchase_million' => ['required', 'integer', 'min:50', 'max:2000'],
            'repair_million' => ['required', 'integer', 'min:0', 'max:400'],
            'term_years' => ['required', 'integer', 'in:5,8,10,12'],
            'applicant_name' => ['required', 'string', 'max:150'],
            'national_id' => ['required', 'digits:10'],
            'contract_number' => ['required', 'string', 'max:100'],
            'contract_date' => ['required', 'string', 'max:30'],
            'buyer_name' => ['required', 'string', 'max:150'],
            'seller_name' => ['required', 'string', 'max:150'],
            'property_address' => ['required', 'string', 'max:1200'],
            'postal_code' => ['nullable', 'digits:10'],
            'deal_price_toman' => ['required', 'integer', 'min:1'],
            'applicant_note' => ['nullable', 'string', 'max:2000'],
            'contract_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $user = $request->user();
        $caps = ['tehran' => 1000, 'province' => 800, 'other' => 600];
        $max = $caps[$data['city']] * ($data['household'] === 'couple' ? 2 : 1);
        if ($data['purchase_million'] > $max) return response()->json(['message' => 'مبلغ وام از سقف انتخابی بیشتر است.'], 422);
        $reference = 'SPAD-M-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        $file = $request->file('contract_file');
        unset($data['contract_file']);
        // Stored on the private local disk; never expose a public storage URL.
        $data['contract_attachment'] = $file->store('mortgage-contracts', 'local');
        try {
            DB::table('mortgage_requests')->insert(array_merge($data, [
            'user_id' => $user->id, 'reference' => $reference, 'status' => 'new',
            'created_at' => now(), 'updated_at' => now(),
            ]));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($data['contract_attachment']);
            throw $e;
        }
        return response()->json(['status' => true, 'reference' => $reference], 201);
    }

    public function mine(Request $request)
    {
        return response()->json(['data' => DB::table('mortgage_requests')
            ->where('user_id', $request->user()->id)->orderByDesc('id')->paginate(20)]);
    }

    public function contractFile($id)
    {
        $row = DB::table('mortgage_requests')->where('id', $id)->first();
        abort_unless($row && $row->contract_attachment, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($row->contract_attachment), 404);
        return $disk->download($row->contract_attachment, 'contract-' . $row->reference . '.' . pathinfo($row->contract_attachment, PATHINFO_EXTENSION));
    }

    public function adminSettings()
    {
        return response()->json(['data' => $this->settings()]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'bond_price' => ['required','integer','min:1','max:10000000'],
            'bond_updated_at' => ['required','string','max:60'],
            'broker_enabled' => ['required','boolean'],
        ]);
        DB::table('mortgage_settings')->updateOrInsert(['id' => 1],
            array_merge($data, ['updated_at' => now()]));
        return response()->json(['data' => $this->settings()]);
    }

    public function adminRequests(Request $request)
    {
        return response()->json(['data' => DB::table('mortgage_requests')
            ->orderByDesc('id')->paginate(30)]);
    }

    public function updateRequest(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required','in:new,reviewing,documents_required,submitted_to_bank,completed,rejected'],
            'admin_note' => ['nullable','string','max:2000'],
        ]);
        $count = DB::table('mortgage_requests')->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()]));
        abort_unless($count, 404);
        return response()->json(['status' => true]);
    }
}
