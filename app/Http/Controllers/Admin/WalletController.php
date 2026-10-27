<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletGateway;
use App\Models\WalletSetting;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    protected $wallets;

    public function __construct(WalletService $wallets)
    {
        $this->wallets = $wallets;
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->leftJoin('wallets', 'wallets.user_id', '=', 'users.id')
            ->select('users.*', DB::raw('COALESCE(wallets.balance, 0) as wallet_balance'), DB::raw("COALESCE(wallets.status, 'active') as wallet_status"));

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.mobile', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('wallet_balance')->paginate(min(100, max(10, (int) $request->get('per_page', 25))));
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                'wallets_count' => Wallet::query()->count(),
                'active_wallets' => Wallet::query()->where('status', 'active')->count(),
                'total_balance' => (int) Wallet::query()->sum('balance'),
                'paid_topups' => (int) WalletTopup::query()->where('status', 'paid')->sum('amount'),
                'transactions_count' => WalletTransaction::query()->count(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $wallet = $this->wallets->walletFor($user);
        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'mobile' => $user->mobile,
                    'email' => $user->email,
                ],
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => (int) $wallet->balance,
                    'status' => $wallet->status,
                ],
                'transactions' => WalletTransaction::query()
                    ->where('wallet_id', $wallet->id)
                    ->with('admin:id,first_name,last_name')
                    ->latest()
                    ->limit(20)
                    ->get(),
                'topups' => WalletTopup::query()
                    ->where('wallet_id', $wallet->id)
                    ->with('gateway:id,title,driver')
                    ->latest()
                    ->limit(20)
                    ->get(),
            ],
        ]);
    }

    public function adjust(Request $request, User $user)
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'description' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $method = $data['direction'] === 'credit' ? 'credit' : 'debit';
            $transaction = $this->wallets->{$method}($user, $data['amount'], [
                'type' => $data['direction'] === 'credit' ? 'admin_credit' : 'admin_debit',
                'admin_id' => auth('admin')->id(),
                'title' => $data['direction'] === 'credit' ? 'افزایش موجودی توسط مدیریت' : 'کاهش موجودی توسط مدیریت',
                'description' => $data['description'],
                'reference' => 'admin-adjust:' . auth('admin')->id() . ':' . uniqid('', true),
                'allow_inactive' => true,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'موجودی کیف پول با موفقیت به‌روزرسانی شد.',
                'data' => $transaction,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function setStatus(Request $request, User $user)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'blocked'])]]);
        $wallet = $this->wallets->walletFor($user);
        $wallet->update(['status' => $data['status']]);
        return response()->json(['status' => true, 'message' => 'وضعیت کیف پول ذخیره شد.']);
    }

    public function transactions(Request $request)
    {
        $query = WalletTransaction::query()->with(['user:id,first_name,last_name,mobile', 'admin:id,first_name,last_name']);
        if ($request->filled('direction')) $query->where('direction', $request->direction);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('mobile', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }
        return $query->latest()->paginate(min(100, max(10, (int) $request->get('per_page', 30))));
    }

    public function settings()
    {
        return response()->json([
            'settings' => WalletSetting::query()->pluck('value', 'key'),
            'gateways' => WalletGateway::query()->orderByDesc('is_default')->orderBy('id')->get()->map(function ($gateway) {
                return [
                    'id' => $gateway->id,
                    'driver' => $gateway->driver,
                    'title' => $gateway->title,
                    'enabled' => $gateway->enabled,
                    'is_default' => $gateway->is_default,
                    'config' => $gateway->maskedConfig(),
                    'has_config' => (bool) $gateway->config_encrypted,
                ];
            }),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'wallet_enabled' => ['required', 'boolean'],
            'topup_enabled' => ['required', 'boolean'],
            'min_topup' => ['required', 'integer', 'min:1000'],
            'max_topup' => ['required', 'integer', 'gte:min_topup'],
            'wallet_title' => ['nullable', 'string', 'max:200'],
            'wallet_intro' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach ($data as $key => $value) WalletSetting::putValue($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        return response()->json(['status' => true, 'message' => 'تنظیمات کیف پول ذخیره شد.']);
    }

    public function saveGateway(Request $request, WalletGateway $gateway = null)
    {
        $data = $request->validate([
            'driver' => ['required', Rule::in(['zarinpal', 'idpay', 'zibal', 'payir'])],
            'title' => ['required', 'string', 'max:100'],
            'enabled' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $gateway = $gateway ?: new WalletGateway();
        $gateway->driver = $data['driver'];
        $gateway->title = $data['title'];
        $gateway->enabled = (bool) $data['enabled'];
        $gateway->is_default = (bool) $data['is_default'];

        $old = $gateway->exists ? $gateway->secureConfig() : [];
        $new = isset($data['config']) ? array_filter($data['config'], function ($value) {
            return $value !== null && $value !== '' && strpos((string) $value, '••') === false;
        }) : [];
        $config = array_merge($old, $new);
        if (!isset($config['currency'])) $config['currency'] = 'T';
        $gateway->setSecureConfig($config)->save();

        if ($gateway->is_default) {
            WalletGateway::query()->where('id', '<>', $gateway->id)->update(['is_default' => false]);
        }

        return response()->json(['status' => true, 'message' => 'درگاه کیف پول ذخیره شد.', 'id' => $gateway->id]);
    }

    public function deleteGateway(WalletGateway $gateway)
    {
        if ($gateway->topups()->exists()) {
            $gateway->update(['enabled' => false]);
            return response()->json(['status' => true, 'message' => 'درگاه به علت داشتن سابقه پرداخت غیرفعال شد.']);
        }
        $gateway->delete();
        return response()->json(['status' => true, 'message' => 'درگاه حذف شد.']);
    }
}
