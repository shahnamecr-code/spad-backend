<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function walletFor(User $user)
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'status' => 'active']
        );
    }

    public function credit(User $user, $amount, array $data = [])
    {
        return $this->apply($user, 'credit', $amount, $data);
    }

    public function debit(User $user, $amount, array $data = [])
    {
        return $this->apply($user, 'debit', $amount, $data);
    }

    protected function apply(User $user, $direction, $amount, array $data)
    {
        $amount = (int) $amount;
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ تراکنش باید بیشتر از صفر باشد.');
        }

        return DB::transaction(function () use ($user, $direction, $amount, $data) {
            $reference = isset($data['reference']) ? (string) $data['reference'] : null;
            if ($reference) {
                $existing = WalletTransaction::query()->where('reference', $reference)->first();
                if ($existing) return $existing;
            }

            Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'status' => 'active']
            );

            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($wallet->status !== 'active' && empty($data['allow_inactive'])) {
                throw new RuntimeException('کیف پول این کاربر غیرفعال است.');
            }

            $before = (int) $wallet->balance;
            if ($direction === 'debit') {
                if ($before < $amount) throw new RuntimeException('موجودی کیف پول کافی نیست.');
                $after = $before - $amount;
            } else {
                $after = $before + $amount;
            }

            $wallet->balance = $after;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'admin_id' => $data['admin_id'] ?? null,
                'direction' => $direction,
                'type' => $data['type'] ?? ($direction === 'credit' ? 'credit' : 'debit'),
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => $data['status'] ?? 'completed',
                'reference' => $reference,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);
        });
    }
}
