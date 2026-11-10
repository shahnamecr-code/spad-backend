<?php

namespace App\Console\Commands;

use App\Services\SpadWhatsAppMarketPublisher;
use App\Services\SpadWhatsAppPreviewCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class WarmSpadWhatsAppPreview extends Command
{
    protected $signature = 'spad:wa-preview-warm {--kind=all : market, city or all} {--province= : Exact province for one city} {--city= : Exact city for one preview}';
    protected $description = 'Cache actual approved WhatsApp posters for responsive, authenticated admin preview; never send any message.';

    public function handle(SpadWhatsAppMarketPublisher $publisher, SpadWhatsAppPreviewCache $cache)
    {
        $kind = (string) $this->option('kind');
        if (!in_array($kind, ['all', 'market', 'city'], true)) {
            $this->error('kind must be market, city, or all.');
            return 2;
        }
        $passed = 0;
        $failed = 0;
        if ($kind !== 'city') {
            try {
                $cache->put('market', null, null, $publisher->buildPreviewFresh('market'));
                $this->line('WA_PREVIEW_CACHE_MARKET=PASS');
                $passed++;
            } catch (Throwable $e) {
                $this->warn('WA_PREVIEW_CACHE_MARKET=UNAVAILABLE');
                Log::warning('SPAD market poster preview cache refresh failed', ['type' => get_class($e)]);
                $failed++;
            }
        }
        if ($kind !== 'market') {
            $provinceOption = trim((string) $this->option('province'));
            $cityOption = trim((string) $this->option('city'));
            if (($provinceOption !== '' || $cityOption !== '') &&
                ($kind !== 'city' || $provinceOption === '' || $cityOption === '' ||
                 mb_strlen($provinceOption) > 80 || mb_strlen($cityOption) > 80)) {
                $this->error('For a single city preview supply --kind=city, --province and --city.');
                return 2;
            }
            if ($provinceOption !== '') {
                $cities = [['province' => $provinceOption, 'city' => $cityOption]];
            } else {
                $settings = $publisher->settings();
                $cities = $settings['cities'] ?? [];
                if (!is_array($cities)) $cities = [];
                $cities = array_merge(SpadWhatsAppMarketPublisher::DEFAULT_CITIES, $cities);
            }
            $seen = [];
            foreach (array_slice($cities, 0, 35) as $place) {
                $province = trim((string) ($place['province'] ?? ''));
                $city = trim((string) ($place['city'] ?? ''));
                if ($province === '' || $city === '') continue;
                $key = $province . '|' . $city;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                try {
                    $cache->put('city', $province, $city,
                        $publisher->buildPreviewFresh('city', $province, $city));
                    $this->line('WA_PREVIEW_CACHE_CITY=' . $city . ':PASS');
                    $passed++;
                } catch (Throwable $e) {
                    $this->warn('WA_PREVIEW_CACHE_CITY=' . $city . ':UNAVAILABLE');
                    Log::warning('SPAD real-estate poster preview cache refresh failed', [
                        'city' => $city, 'exception_type' => get_class($e),
                    ]);
                    $failed++;
                }
            }
        }
        $this->line('WA_PREVIEW_CACHE_RESULT=' . $passed . ' ok, ' . $failed . ' unavailable');
        return $passed > 0 ? 0 : 1;
    }
}
