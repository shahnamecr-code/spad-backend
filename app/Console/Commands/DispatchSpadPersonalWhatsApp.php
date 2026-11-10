<?php

namespace App\Console\Commands;

use App\Services\SpadWhatsAppPersonalDelivery;
use Illuminate\Console\Command;
use Throwable;

class DispatchSpadPersonalWhatsApp extends Command
{
    protected $signature = 'spad:wa-personal-dispatch';
    protected $description = 'Send due genuine SPAD market posters only to the QR-linked account self-chat when explicitly enabled.';

    public function handle(SpadWhatsAppPersonalDelivery $delivery): int
    {
        try {
            $result = $delivery->runScheduled();
            $this->line('SPAD_WA_PERSONAL_STATUS=' . ($result['status'] ?? 'unknown'));
            return 0;
        } catch (Throwable $e) {
            $this->warn('SPAD_WA_PERSONAL_STATUS=failed');
            return 1;
        }
    }
}
