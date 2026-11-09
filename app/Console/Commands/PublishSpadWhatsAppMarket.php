<?php

namespace App\Console\Commands;

use App\Services\SpadWhatsAppMarketPublisher;
use Illuminate\Console\Command;

class PublishSpadWhatsAppMarket extends Command
{
    protected $signature = 'spad:whatsapp-market {--dry-run : Report scheduled slots without posting}';
    protected $description = 'Publish scheduled, source-verified SPAD market posters to eligible WhatsApp groups.';

    public function handle(SpadWhatsAppMarketPublisher $publisher)
    {
        $result = $publisher->run((bool) $this->option('dry-run'));
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return 0;
    }
}
