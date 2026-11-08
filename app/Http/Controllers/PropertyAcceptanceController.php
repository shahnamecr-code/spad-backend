<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SpadSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PropertyAcceptanceController extends Controller
{
    private const STATUS = ['new', 'contacted', 'visit_scheduled', 'under_review', 'accepted', 'declined', 'closed'];

    private function installed(): void
    {
        abort_unless(Schema::hasTable('spad_property_acceptance_requests') &&
            Schema::hasTable('spad_property_acceptance_activity'), 503,
            'سرویس پذیرش ملک هنوز فعال نشده است.');
    }

    private function superAdmin(): void
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasRole('Super Admin'), 403,
            'دسترسی پرونده‌های پذیرش ملک فقط برای مدیر اصلی مجاز است.');
        $this->installed();
    }

    public function store(Request $request)
    {
        $this->installed();
        $data = $request->validate([
            'quote_token' => ['required', 'string', 'size:48', 'regex:/^[a-zA-Z0-9]+$/D'],
            'owner_confirmed' => ['required', 'accepted'],
            'contact_consent' => ['required', 'accepted'],
        ]);
        $user = $request->user();
        abort_unless($user && $user instanceof User, 401);
        $token = $data['quote_token'];
        $quote = Cache::get('spad:asset:quote:' . $token);
        if (!is_array($quote) || (int) ($quote['user_id'] ?? 0) !== (int) $user->id) {
            return response()->json(['status' => false,
                'message' => 'اعتبار برآورد پایان یافته است. لطفاً دوباره قیمت ملک را دریافت کنید.'], 410);
        }
        $postal = (string) ($quote['postal_code'] ?? '');
        $valuation = $quote['valuation'] ?? null;
        if (!preg_match('/^[0-9]{10}$/D', $postal) || !is_array($valuation) ||
            !in_array(($valuation['property_kind'] ?? ''), ['apartment', 'house', 'land', 'shop', 'office'], true) ||
            !is_numeric($valuation['estimated_total_toman'] ?? null) ||
            (float) $valuation['estimated_total_toman'] <= 0 ||
            (float) $valuation['estimated_total_toman'] > 999999999999999 ||
            !is_numeric($valuation['area_m2'] ?? null) ||
            (float) $valuation['area_m2'] < 10 ||
            (float) $valuation['area_m2'] > 10000) {
            return response()->json(['status' => false, 'message' => 'اطلاعات قیمت‌گذاری معتبر نیست.'], 422);
        }
        $hash = hash_hmac('sha256', $token . '|' . $user->id, config('app.key'));
        $existing = DB::table('spad_property_acceptance_requests')->where('quote_hash', $hash)->first();
        if ($existing) return response()->json(['status' => true, 'data' => [
            'id' => $existing->id, 'status' => $existing->status,
        ], 'message' => 'این درخواست قبلاً ثبت شده است.'])->header('Cache-Control', 'private, no-store');

        $snapshot = [
            'estimate' => $valuation,
            'filters' => $quote['filters'] ?? [],
            'quoted_at' => now()->toIso8601String(),
        ];
        try {
            $id = DB::transaction(function () use ($quote, $valuation, $postal, $hash, $user, $snapshot) {
                $id = DB::table('spad_property_acceptance_requests')->insertGetId([
                    'user_id' => $user->id,
                    'quote_hash' => $hash,
                    'postal_code_encrypted' => Crypt::encryptString($postal),
                    'address_encrypted' => Crypt::encryptString(json_encode($quote['address'] ?? [], JSON_UNESCAPED_UNICODE)),
                    'province' => mb_substr((string) ($quote['province'] ?? ''), 0, 120),
                    'city' => mb_substr((string) ($quote['city'] ?? ''), 0, 120),
                    'property_kind' => $valuation['property_kind'],
                    'area_m2' => $valuation['area_m2'],
                    'estimated_total_toman' => (int) round($valuation['estimated_total_toman']),
                    'range_low_toman' => isset($valuation['range_low_toman']) ? (int) round($valuation['range_low_toman']) : null,
                    'range_high_toman' => isset($valuation['range_high_toman']) ? (int) round($valuation['range_high_toman']) : null,
                    'market_source' => mb_substr((string) ($valuation['source'] ?? 'نامشخص'), 0, 100),
                    'coverage' => mb_substr((string) ($valuation['coverage'] ?? ''), 0, 32),
                    'valuation_snapshot_encrypted' => Crypt::encryptString(json_encode($snapshot, JSON_UNESCAPED_UNICODE)),
                    'owner_confirmed' => true,
                    'contact_consent' => true,
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('spad_property_acceptance_activity')->insert([
                    'request_id' => $id, 'admin_id' => null, 'action' => 'submitted',
                    'from_status' => null, 'to_status' => 'new', 'created_at' => now(),
                ]);
                return $id;
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            // Retry/double click must not create two sales leads.
            $old = DB::table('spad_property_acceptance_requests')->where('quote_hash', $hash)->first();
            if ($old) return response()->json(['status' => true,
                'data' => ['id' => $old->id, 'status' => $old->status]]);
            throw $e;
        }
        try {
            if ($user->mobile) app(SpadSmsService::class)->notify(
                'property_acceptance_submitted', (string) $user->mobile,
                ['id' => $id], 'acceptance:' . $id . ':submitted'
            );
        } catch (\Throwable $ignored) { /* Notifications must not roll back a lead. */ }
        return response()->json(['status' => true,
            'data' => ['id' => $id, 'status' => 'new'],
            'message' => 'درخواست فروش ملک ثبت شد. کارشناسان اسپاد پس از بررسی با شما تماس می‌گیرند.',
        ], 201)->header('Cache-Control', 'private, no-store');
    }

    public function mine(Request $request)
    {
        $this->installed();
        $items = DB::table('spad_property_acceptance_requests')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id','city','property_kind','area_m2','estimated_total_toman','status','created_at']);
        return response()->json(['status' => true, 'data' => $items])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request)
    {
        $this->superAdmin();
        $data = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUS)],
            'city' => ['nullable','string','max:120'],
            'search' => ['nullable','string','max:80'],
            'page' => ['nullable','integer','min:1','max:100000'],
        ]);
        $q = DB::table('spad_property_acceptance_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id');
        if (!empty($data['status'])) $q->where('r.status', $data['status']);
        if (!empty($data['city'])) $q->where('r.city', $data['city']);
        if (!empty($data['search'])) {
            $term = trim($data['search']);
            $q->where(function ($query) use ($term) {
                if (ctype_digit($term)) $query->orWhere('r.id', (int) $term);
                $query->orWhere('u.mobile', 'like', '%' . str_replace(['%','_'], '', $term) . '%');
            });
        }
        $items = $q->orderByDesc('r.id')->paginate(20, [
            'r.id','r.user_id','u.first_name','u.last_name','u.mobile',
            'r.city','r.province','r.property_kind','r.area_m2','r.estimated_total_toman',
            'r.status','r.assigned_admin_id','r.follow_up_at','r.created_at'
        ]);
        $summary = DB::table('spad_property_acceptance_requests')
            ->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status');
        return response()->json(['status' => true, 'data' => $items, 'summary' => $summary])
            ->header('Cache-Control','private, no-store');
    }

    public function show($id)
    {
        $this->superAdmin();
        $item = DB::table('spad_property_acceptance_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.id', (int) $id)
            ->first(['r.*','u.first_name','u.last_name','u.mobile']);
        abort_unless($item, 404);
        $item->postal_code = Crypt::decryptString($item->postal_code_encrypted);
        $item->address = json_decode(Crypt::decryptString($item->address_encrypted), true) ?: [];
        $item->valuation = json_decode(Crypt::decryptString($item->valuation_snapshot_encrypted), true) ?: [];
        unset($item->postal_code_encrypted, $item->address_encrypted, $item->valuation_snapshot_encrypted, $item->quote_hash);
        $history = DB::table('spad_property_acceptance_activity')->where('request_id',$item->id)
            ->orderByDesc('id')->limit(50)->get();
        return response()->json(['status' => true, 'data' => $item, 'history' => $history])
            ->header('Cache-Control','private, no-store');
    }

    public function update(Request $request, $id)
    {
        $this->superAdmin();
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUS)],
            'assigned_admin_id' => ['nullable','integer','exists:admins,id'],
            'follow_up_at' => ['nullable','date'],
            'admin_notes' => ['nullable','string','max:2000'],
        ]);
        $admin = auth('admin')->user();
        $changed = DB::transaction(function () use ($id, $data, $admin) {
            $row = DB::table('spad_property_acceptance_requests')->where('id',(int)$id)->lockForUpdate()->first();
            abort_unless($row, 404);
            $status = $data['status'];
            $fields = [
                'status' => $status,
                'assigned_admin_id' => $data['assigned_admin_id'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
                'updated_at' => now(),
            ];
            if ($status === 'contacted' && $row->status !== 'contacted')
                $fields['last_contacted_at'] = now();
            DB::table('spad_property_acceptance_requests')->where('id',$row->id)->update($fields);
            if ($status !== $row->status || $row->assigned_admin_id != ($fields['assigned_admin_id'])) {
                DB::table('spad_property_acceptance_activity')->insert([
                    'request_id' => $row->id,'admin_id' => $admin->id,
                    'action' => $status !== $row->status ? 'status_changed' : 'assignment_changed',
                    'from_status' => $row->status,'to_status' => $status,'created_at' => now(),
                ]);
            }
            return ['id'=>$row->id,'status'=>$status,'prior'=>$row->status,'user_id'=>$row->user_id];
        }, 3);
        if ($changed['status'] !== $changed['prior']) {
            try {
                $user = User::find($changed['user_id']);
                if ($user && $user->mobile) app(SpadSmsService::class)->notify(
                    'property_acceptance_update',(string)$user->mobile,
                    ['id'=>$changed['id']], 'acceptance:'.$changed['id'].':'.$changed['status']
                );
            } catch (\Throwable $ignored) {}
        }
        return response()->json(['status'=>true,'data'=>['id'=>$changed['id'],'status'=>$changed['status']],
            'message'=>'پرونده پذیرش ملک به‌روزرسانی شد.'])
            ->header('Cache-Control','private, no-store');
    }
}
