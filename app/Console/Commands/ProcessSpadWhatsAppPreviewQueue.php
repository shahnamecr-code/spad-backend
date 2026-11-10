<?php

namespace App\Console\Commands;

use App\Services\SpadWhatsAppPreviewBuilder;
use Illuminate\Console\Command;

class ProcessSpadWhatsAppPreviewQueue extends Command
{
    protected $signature = 'spad:wa-preview-process';
    protected $description = 'Generate requested official SPAD approved market preview images in CLI, never in PHP-FPM.';

    public function handle(SpadWhatsAppPreviewBuilder $builder): int
    {
        $result=$builder->process(4);
        $this->line('WA_PREVIEW_BUILDER_STATUS='.$result['status']);
        $this->line('WA_PREVIEW_BUILDER_COUNT='.($result['built'] ?? 0));
        return 0;
    }
}
