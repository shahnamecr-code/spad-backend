<?php

namespace App\Services;

use App\Models\InquiryRequest;
use App\Models\PartnerPanel;
use App\Models\PartnerWallet;
use App\Models\PartnerWalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerWalletService
{
    public function walletFor(PartnerPanel $panel)
    {
        return PartnerWallet::query()->firstOrCreate(
            ['partner_panel_id' => $panel->id],
            ['balance' => 0, 'status' => 'active']
        );
    }

    public function creditInquiry(PartnerPanel $panel, InquiryRequest $inquiry, $amount)
    {
        $amount = (int) $amount;
        if ($amount <= 0) return null;

        return DB::transaction(function () use ($panel, $inquiry, $amount) {
            $reference = 'partner-inquiry-payout:' . $inquiry->id;
            $existing = PartnerWalletTransaction::query()->where('reference', $reference)->first();
            if ($existing) return $existing;

            PartnerWallet::query()->firstOrCreate(
                ['partner_panel_id' => $panel->id],
                ['balance' => 0, 'status' => 'active']
            );

            $wallet = PartnerWallet::query()->where('partner_panel_id', $panel->id)->lockForUpdate()->firstOrFail();
            if ($wallet->status !== 'active') {
                throw new RuntimeException('کیف پول پنل همکاری غیرفعال است.');
            }

            $before = (int) $wallet->balance;
            $after = $before + $amount;
            $wallet->update(['balance' => $after]);

            return PartnerWalletTransaction::create([
                'partner_wallet_id' => $wallet->id,
                'partner_panel_id' => $panel->id,
                'inquiry_request_id' => $inquiry->id,
                'direction' => 'credit',
                'type' => 'inquiry_commission',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'completed',
                'reference' => $reference,
                'title' => 'دستمزد انجام استعلام',
                'description' => 'دستمزد سفارش ' . $inquiry->reference,
                'meta' => [
                    'inquiry_reference' => $inquiry->reference,
                    'service_title' => optional($inquiry->service)->title,
                ],
            ]);
        });
    }

    public function summary(PartnerPanel $panel)
    {
        $wallet = $this->walletFor($panel);
        $query = PartnerWalletTransaction::query()
            ->where('partner_panel_id', $panel->id)
            ->where('status', 'completed')
            ->where('direction', 'credit');

        return [
            'balance' => (int) $wallet->balance,
            'status' => $wallet->status,
            'total_earned' => (int) (clone $query)->sum('amount'),
            'today_earned' => (int) (clone $query)->whereDate('created_at', today())->sum('amount'),
            'paid_orders' => (int) (clone $query)->where('type', 'inquiry_commission')->count(),
            'latest_transactions' => PartnerWalletTransaction::query()
                ->where('partner_panel_id', $panel->id)
                ->where('status', 'completed')
                ->latest()->limit(8)->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'direction' => $item->direction,
                        'type' => $item->type,
                        'amount' => (int) $item->amount,
                        'balance_after' => (int) $item->balance_after,
                        'reference' => $item->reference,
                        'title' => $item->title,
                        'description' => $item->description,
                        'created_at' => optional($item->created_at)->toIso8601String(),
                    ];
                })->values(),
        ];
    }
}
