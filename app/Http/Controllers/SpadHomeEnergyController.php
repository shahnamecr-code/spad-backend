<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\HomeDoctor\PrivatePhoto;
use App\Services\HomeEnergy\Ledger;
use App\Services\HomeEnergy\UtilityCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpadHomeEnergyController extends Controller
{
    private const LOCATIONS = 'spad_home_energy_locations';
    private const ACCOUNTS = 'spad_home_energy_accounts';
    private const BILLS = 'spad_home_energy_bills';

    public function capabilities(UtilityCapabilities $capabilities)
    {
        return $this->data($capabilities->read());
    }

    public function dashboard(Request $request)
    {
        $owner = $this->owner($request);
        $today = now('Asia/Tehran')->toDateString();
        $bills = $this->bills($owner);
        $unpaid = (clone $bills)->whereNull('bills.paid_on');
        $next = (clone $unpaid)->where('bills.due_on', '>=', $today)->orderBy('bills.due_on')->orderBy('bills.id')->first();
        $locations = DB::table(self::LOCATIONS)->where('user_id', $owner)->orderBy('title')->orderBy('id')->get(['id', 'title']);
        $accounts = DB::table(self::ACCOUNTS)->where('user_id', $owner)->orderBy('title')->orderBy('id')->get()->map([Ledger::class, 'account']);
        $reminders = (clone $unpaid)->where('bills.remind_on', '<=', $today)->orderBy('bills.remind_on')
            ->orderBy('bills.id')->limit(10)->get()->map([Ledger::class, 'bill']);

        return $this->data([
            'locations' => $locations,
            'accounts' => $accounts,
            'totals' => [
                'bill_count' => (int) $bills->count(),
                'unpaid_count' => (int) $unpaid->count(),
                'unpaid_toman' => (int) $unpaid->sum('bills.amount_toman'),
                'overdue_count' => (int) (clone $unpaid)->where('bills.due_on', '<', $today)->count(),
                'next_due' => $next ? Ledger::bill($next) : null,
            ],
            'reminders' => $reminders,
        ]);
    }

    public function storeLocation(Request $request)
    {
        $owner = $this->owner($request);
        $data = $request->validate(['title' => 'required|string|min:2|max:100']);
        $id = DB::table(self::LOCATIONS)->insertGetId($data + ['user_id' => $owner, 'created_at' => now(), 'updated_at' => now()]);
        return $this->data($this->location($owner, $id), 201);
    }

    public function updateLocation(Request $request, int $id)
    {
        $owner = $this->owner($request);
        $data = $request->validate(['title' => 'required|string|min:2|max:100']);
        DB::transaction(function () use ($owner, $id, $data) {
            $this->location($owner, $id, true);
            DB::table(self::LOCATIONS)->where('id', $id)->update($data + ['updated_at' => now()]);
        });
        return $this->data($this->location($owner, $id));
    }

    public function deleteLocation(Request $request, int $id)
    {
        $owner = $this->owner($request);
        DB::transaction(function () use ($owner, $id) {
            $this->location($owner, $id, true);
            abort_if(DB::table(self::ACCOUNTS)->where('location_id', $id)->exists(), 409, 'برای حذف این محل ابتدا اشتراک‌های آن را جابه‌جا یا حذف کنید.');
            DB::table(self::LOCATIONS)->where('id', $id)->delete();
        });
        return $this->empty();
    }

    public function storeAccount(Request $request)
    {
        $owner = $this->owner($request);
        $request->merge(['identifier' => Ledger::digits($request->input('identifier'))]);
        $data = $request->validate([
            'location_id' => 'required|integer|min:1',
            'title' => 'required|string|min:2|max:100',
            'utility' => ['required', Rule::in(array_keys(Ledger::UTILITIES))],
            'identifier' => 'required|regex:/\A[0-9]{5,40}\z/',
        ], ['identifier.*' => 'شناسهٔ قبض یا اشتراک باید بین ۵ تا ۴۰ رقم باشد.']);
        [$account, $created] = $this->saveAccount($owner, $data);
        return $this->data(Ledger::account($account), $created ? 201 : 200);
    }

    public function account(Request $request, int $id)
    {
        $owner = $this->owner($request);
        $request->validate(['page' => 'nullable|integer|min:1|max:10000']);
        $account = $this->accountRow($owner, $id);
        $query = $this->bills($owner)->where('bills.account_id', $id);
        $page = (clone $query)->orderByDesc('bills.created_at')->orderByDesc('bills.id')->paginate(15);
        $periods = $query->whereNotNull('bills.period_start')->whereNotNull('bills.period_end')
            ->orderByDesc('bills.period_end')->orderByDesc('bills.id')->limit(24)->get()
            ->map(static function ($record) {
                return [
                    'period_start' => $record->period_start, 'period_end' => $record->period_end,
                    'amount_toman' => (int) $record->amount_toman,
                    'consumption' => $record->consumption === null ? null : (float) $record->consumption,
                ];
            })->all();

        return $this->data([
            'account' => Ledger::account($account),
            'bills' => $page->getCollection()->map([Ledger::class, 'bill'])->values(),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage()],
            'periods' => $periods,
            'comparison' => Ledger::compare($periods),
        ]);
    }

    public function updateAccount(Request $request, int $id)
    {
        $owner = $this->owner($request);
        $data = $request->validate(['location_id' => 'required|integer|min:1', 'title' => 'required|string|min:2|max:100']);
        DB::transaction(function () use ($owner, $id, $data) {
            $this->location($owner, $data['location_id'], true);
            $this->accountRow($owner, $id, true);
            DB::table(self::ACCOUNTS)->where('id', $id)->update($data + ['updated_at' => now()]);
        });
        return $this->data(Ledger::account($this->accountRow($owner, $id)));
    }

    public function deleteAccount(Request $request, int $id)
    {
        $owner = $this->owner($request);
        DB::transaction(function () use ($owner, $id) {
            $this->accountRow($owner, $id, true);
            foreach (DB::table(self::BILLS)->where('account_id', $id)->whereNotNull('receipt_path')->pluck('receipt_path') as $path) {
                $this->removeFile($path);
            }
            DB::table(self::ACCOUNTS)->where('id', $id)->delete();
        });
        return $this->empty();
    }

    public function storeBill(Request $request, int $id)
    {
        $owner = $this->owner($request);
        [$bill, $created] = DB::transaction(function () use ($request, $owner, $id) {
            $account = $this->accountRow($owner, $id, true);
            $data = $this->billInput($request, $account);
            $existing = DB::table(self::BILLS)->where('account_id', $id)->where('duplicate_hash', $data['duplicate_hash'])->first();
            if ($existing) {
                return [$existing, false];
            }
            $billId = DB::table(self::BILLS)->insertGetId($data + ['account_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            return [DB::table(self::BILLS)->find($billId), true];
        });
        return $this->data(Ledger::bill($bill), $created ? 201 : 200);
    }

    public function updateBill(Request $request, int $id)
    {
        $owner = $this->owner($request);
        $bill = DB::transaction(function () use ($request, $owner, $id) {
            $bill = $this->billRow($owner, $id);
            $account = $this->accountRow($owner, $bill->account_id, true);
            $bill = $this->billRow($owner, $id);
            $data = $this->billInput($request, $account);
            abort_if(DB::table(self::BILLS)->where('account_id', $account->id)->where('id', '!=', $id)
                ->where('duplicate_hash', $data['duplicate_hash'])->exists(), 409, 'قبضی با همین مشخصات از قبل ثبت شده است.');
            DB::table(self::BILLS)->where('id', $id)->update($data + ['updated_at' => now()]);
            return $this->billRow($owner, $bill->id);
        });
        return $this->data(Ledger::bill($bill));
    }

    public function deleteBill(Request $request, int $id)
    {
        $owner = $this->owner($request);
        DB::transaction(function () use ($owner, $id) {
            $bill = $this->billRow($owner, $id);
            $this->accountRow($owner, $bill->account_id, true);
            $bill = $this->billRow($owner, $id);
            $this->removeFile($bill->receipt_path);
            DB::table(self::BILLS)->where('id', $id)->delete();
        });
        return $this->empty();
    }

    public function uploadReceipt(Request $request, int $id)
    {
        $owner = $this->owner($request);
        $bill = $this->billRow($owner, $id);
        $data = $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192']);
        [$bytes] = PrivatePhoto::process($data['image']);
        $path = 'home-energy/' . $id . '/' . Str::uuid() . '.jpg';

        try {
            [$bill, $oldPath] = DB::transaction(function () use ($owner, $id, $bill, $bytes, $path) {
                $this->accountRow($owner, $bill->account_id, true);
                $bill = $this->billRow($owner, $id);
                abort_unless(PrivatePhoto::disk()->put($path, $bytes), 503, 'ذخیرهٔ رسید انجام نشد.');
                DB::table(self::BILLS)->where('id', $id)->update(['receipt_path' => $path, 'updated_at' => now()]);
                return [$this->billRow($owner, $id), $bill->receipt_path];
            });
        } catch (\Throwable $error) {
            PrivatePhoto::disk()->delete($path);
            throw $error;
        }
        if ($oldPath) {
            PrivatePhoto::disk()->delete($oldPath);
        }
        return $this->data(Ledger::bill($bill), 201);
    }

    public function receipt(Request $request, int $id)
    {
        $bill = $this->billRow($this->owner($request), $id);
        abort_unless($bill->receipt_path && PrivatePhoto::disk()->exists($bill->receipt_path), 404);
        return response(PrivatePhoto::disk()->get($bill->receipt_path), 200, [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline; filename="receipt.jpg"',
        ]);
    }

    public function deleteReceipt(Request $request, int $id)
    {
        $owner = $this->owner($request);
        DB::transaction(function () use ($owner, $id) {
            $bill = $this->billRow($owner, $id);
            $this->accountRow($owner, $bill->account_id, true);
            $bill = $this->billRow($owner, $id);
            $this->removeFile($bill->receipt_path);
            DB::table(self::BILLS)->where('id', $id)->update(['receipt_path' => null, 'updated_at' => now()]);
        });
        return $this->data(Ledger::bill($this->billRow($owner, $id)));
    }

    public function buildingSources(Request $request)
    {
        $owner = $this->owner($request);
        if (!$this->buildingSourcesAvailable()) {
            return $this->data([]);
        }
        $sources = [];
        foreach ($this->buildingUnits($owner)->limit(100)->get() as $unit) {
            foreach (Ledger::UTILITIES as $kind => $info) {
                $reference = Ledger::digits($unit->{$info['unit_field']});
                if ($reference && preg_match('/\A[0-9]{5,40}\z/', $reference)) {
                    $sources[] = [
                        'unit_id' => (int) $unit->id, 'building_name' => $unit->building_name, 'unit_label' => $unit->unit_label,
                        'utility' => $kind, 'label' => $info['label'], 'last_four' => substr($reference, -4),
                    ];
                }
            }
        }
        return $this->data($sources);
    }

    public function importBuilding(Request $request)
    {
        $owner = $this->owner($request);
        $data = $request->validate([
            'unit_id' => 'required|integer|min:1', 'location_id' => 'required|integer|min:1',
            'utility' => ['required', Rule::in(array_keys(Ledger::UTILITIES))],
            'consent' => 'accepted',
        ], ['consent.accepted' => 'رضایت خود را برای انتقال شناسهٔ همین اشتراک ثبت کنید.']);
        abort_unless($this->buildingSourcesAvailable(), 503, 'اطلاعات واحد در حال حاضر در دسترس نیست.');
        [$account, $created] = DB::transaction(function () use ($owner, $data) {
            $unit = $this->buildingUnits($owner)->where('u.id', $data['unit_id'])->lockForUpdate()->first();
            abort_unless($unit, 404, 'شناسهٔ این واحد در حساب شما یافت نشد.');
            $kind = Ledger::UTILITIES[$data['utility']];
            $identifier = Ledger::digits($unit->{$kind['unit_field']});
            abort_unless($identifier && preg_match('/\A[0-9]{5,40}\z/', $identifier), 422, 'شناسهٔ قابل انتقال برای این اشتراک ثبت نشده است.');
            return $this->saveAccount($owner, $data + [
                'identifier' => $identifier, 'title' => mb_substr($kind['label'] . ' · ' . $unit->building_name, 0, 100),
            ], (int) $unit->id);
        });
        return $this->data(Ledger::account($account), $created ? 201 : 200);
    }

    private function buildingSourcesAvailable(): bool
    {
        return Schema::hasTable('spad_buildings') && Schema::hasTable('spad_building_units') &&
            Schema::hasColumns('spad_building_units', array_column(Ledger::UTILITIES, 'unit_field'));
    }

    private function buildingUnits(int $owner)
    {
        return DB::table('spad_building_units as u')->join('spad_buildings as b', 'b.id', '=', 'u.building_id')
            ->where('u.user_id', $owner)->where('u.status', 'active')
            ->select(array_merge(['u.id', 'u.label as unit_label', 'b.name as building_name'], array_map(function ($column) {
                return 'u.' . $column;
            }, array_column(Ledger::UTILITIES, 'unit_field'))));
    }

    private function saveAccount(int $owner, array $data, ?int $unit = null): array
    {
        $hash = Ledger::identifierHash($data['identifier'], $owner);
        $key = ['user_id' => $owner, 'utility' => $data['utility'], 'identifier_hash' => $hash];
        return DB::transaction(function () use ($owner, $data, $unit, $key) {
            $this->location($owner, $data['location_id'], true);
            $query = DB::table(self::ACCOUNTS)->where($key);
            $created = DB::table(self::ACCOUNTS)->insertOrIgnore($key + [
                'location_id' => $data['location_id'],
                'title' => $data['title'],
                'identifier_encrypted' => Crypt::encryptString($data['identifier']),
                'source_unit_id' => $unit, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $account = $query->first();
            abort_unless($account, 503, 'ذخیرهٔ اشتراک انجام نشد.');
            return [$account, (bool) $created];
        });
    }

    private function billInput(Request $request, $account): array
    {
        $request->merge(['payment_identifier' => Ledger::digits($request->input('payment_identifier'))]);
        $input = $request->validate([
            'amount_toman' => 'required|integer|min:0|max:999999999999',
            'consumption' => 'nullable|numeric|min:0|max:9999999999.999',
            'period_start' => 'nullable|required_with:period_end|date_format:Y-m-d',
            'period_end' => 'nullable|required_with:period_start|date_format:Y-m-d|after_or_equal:period_start',
            'issued_on' => 'nullable|date_format:Y-m-d',
            'due_on' => 'nullable|date_format:Y-m-d',
            'remind_on' => 'nullable|date_format:Y-m-d',
            'paid_on' => 'nullable|date_format:Y-m-d|before_or_equal:' . now('Asia/Tehran')->toDateString(),
            'payment_identifier' => 'nullable|regex:/\A[0-9]{5,40}\z/',
        ], [
            'amount_toman.*' => 'مبلغ قبض را به تومان به‌صورت عدد وارد کنید.',
            'period_end.*' => 'تاریخ پایان دوره باید هم‌روز یا پس از تاریخ شروع باشد.',
            'paid_on.*' => 'تاریخ پرداخت دستی باید امروز یا پیش از آن باشد.',
            'payment_identifier.*' => 'شناسهٔ پرداخت باید بین ۵ تا ۴۰ رقم باشد.',
        ]);

        $fields = ['consumption', 'period_start', 'period_end', 'issued_on', 'due_on', 'remind_on', 'paid_on', 'payment_identifier'];
        $input += array_fill_keys($fields, null);
        if ($input['period_end'] === null && $input['issued_on'] === null && $input['due_on'] === null) {
            throw ValidationException::withMessages(['issued_on' => 'برای ثبت قبض، تاریخ صدور، سررسید یا بازهٔ مصرف را وارد کنید.']);
        }
        if ($input['due_on'] && $input['issued_on'] && $input['due_on'] < $input['issued_on']) {
            throw ValidationException::withMessages(['due_on' => 'سررسید نمی‌تواند پیش از تاریخ صدور باشد.']);
        }
        if ($input['paid_on'] && $input['issued_on'] && $input['paid_on'] < $input['issued_on']) {
            throw ValidationException::withMessages(['paid_on' => 'تاریخ پرداخت نمی‌تواند پیش از تاریخ صدور باشد.']);
        }
        if ($input['remind_on'] && $input['due_on'] && $input['remind_on'] > $input['due_on']) {
            throw ValidationException::withMessages(['remind_on' => 'یادآوری باید در سررسید یا پیش از آن باشد.']);
        }
        if ($input['consumption'] !== null && ($account->utility === 'phone' || $input['period_start'] === null)) {
            throw ValidationException::withMessages(['consumption' => 'مصرف فقط برای آب، برق و گاز با بازهٔ شروع و پایان ثبت می‌شود.']);
        }

        $dedupe = $input['payment_identifier'] !== null ? 'payment:' . $input['payment_identifier']
            : 'record:' . implode('|', [$input['period_start'], $input['period_end'], $input['issued_on'], $input['due_on'], $input['amount_toman']]);
        $input['duplicate_hash'] = Ledger::identifierHash($dedupe, (int) $account->user_id);
        $input['payment_identifier_encrypted'] = $input['payment_identifier'] === null ? null : Crypt::encryptString($input['payment_identifier']);
        unset($input['payment_identifier']);
        return $input;
    }

    private function bills(int $owner)
    {
        return DB::table(self::BILLS . ' as bills')->join(self::ACCOUNTS . ' as accounts', 'accounts.id', '=', 'bills.account_id')
            ->where('accounts.user_id', $owner)->select('bills.*');
    }

    private function billRow(int $owner, int $id)
    {
        $record = $this->bills($owner)->where('bills.id', $id)->first();
        abort_unless($record, 404);
        return $record;
    }

    private function accountRow(int $owner, int $id, bool $lock = false)
    {
        $query = DB::table(self::ACCOUNTS)->where('user_id', $owner)->where('id', $id);
        $record = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($record, 404);
        return $record;
    }

    private function location(int $owner, int $id, bool $lock = false)
    {
        $query = DB::table(self::LOCATIONS)->where('user_id', $owner)->where('id', $id);
        $record = ($lock ? $query->lockForUpdate() : $query)->first(['id', 'title']);
        abort_unless($record, 404);
        return $record;
    }

    private function owner(Request $request): int
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->currentAccessToken()->name ?? null) !== 'partner-token', 403);
        return (int) $user->getKey();
    }

    private function removeFile(?string $path): void
    {
        $disk = PrivatePhoto::disk();
        abort_if($path && $disk->exists($path) && !$disk->delete($path), 503, 'حذف فایل رسید انجام نشد.');
    }

    private function data($data, int $status = 200)
    {
        return response()->json(['data' => $data], $status)->header('Cache-Control', 'private, no-store');
    }

    private function empty()
    {
        return response()->noContent()->header('Cache-Control', 'private, no-store');
    }
}
