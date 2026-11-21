<?php

namespace App\Services\HomeEnergy;

use App\Models\InquiryService;
use App\Models\InquirySetting;
use App\Services\ApiIrClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UtilityCapabilities
{
    public function read(): array
    {
        $registry = Schema::hasTable('spad_web_service_connections')
            ? DB::table('spad_web_service_connections')->where('code', 'utility-bills')->first()
            : null;
        $configured = $registry && $registry->enabled && $registry->mode === 'configured' && !empty($registry->provider);
        $supported = $configured && in_array(strtolower(trim($registry->provider)), ['api.ir', 'api-ir', 'api_ir'], true);
        $services = [];
        if ($supported && Schema::hasTable('inquiry_services') && Schema::hasTable('inquiry_settings') &&
            InquirySetting::valueOf('inquiries_enabled', '1') === '1') {
            try {
                app(ApiIrClient::class)->assertReady();
                $slugs = [
                    'water' => 'property-apiir-water-bill',
                    'electricity' => 'property-apiir-power-bill',
                    'gas' => 'property-apiir-gas-bill',
                ];
                $active = InquiryService::query()->where('enabled', true)->whereIn('slug', array_values($slugs))->pluck('slug')->all();
                $services = array_filter($slugs, static function ($slug) use ($active) { return in_array($slug, $active, true); });
            } catch (\Throwable $error) {
                // Checking configuration must never contact a paid service.
            }
        }

        return [
            'manual' => true,
            'inquiry_configured' => (bool) $configured,
            'inquiry_operational' => null,
            'inquiry_services' => (object) $services,
            'payment_enabled' => false,
            'ocr_enabled' => false,
            'receipt_images_enabled' => function_exists('imagecreatefromstring') && function_exists('imagejpeg'),
        ];
    }
}
