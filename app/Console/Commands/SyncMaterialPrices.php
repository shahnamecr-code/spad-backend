<?php
namespace App\Console\Commands;
use App\Http\Controllers\MaterialPricesController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class SyncMaterialPrices extends Command
{
    protected $signature='materials:sync';
    protected $description='Safely sync verified SPAD building material quotes';
    public function handle()
    {
        $lock=Cache::lock('spad-materials-prices-sync',180);
        if(!$lock->get()){ $this->info('SYNC_ALREADY_RUNNING');return 0; }
        try {
            // Honor the administrator's refresh cadence even though GitHub
            // dispatches the scheduler every 15 minutes.
            if (\Illuminate\Support\\Facades\\Schema::hasTable('material_price_settings')) {
                $config=\Illuminate\Support\\Facades\\DB::table('material_price_settings')->where('id',1)->first();
                if ($config && $config->sync_enabled && $config->last_sync_at) {
                    $age=now()->diffInMinutes(\Carbon\Carbon::parse($config->last_sync_at));
                    if ($age < max(15,(int)$config->refresh_minutes)) {
                        $this->info('SYNC_NOT_DUE age='.$age.'m');
                        return 0;
                    }
                }
            }
            $result=(new MaterialPricesController())->sync();
            $this->info(($result['ok']?'SYNC_OK':'SYNC_NOT_CONNECTED').': '.($result['message']??''));
            // An unset provider key is a configuration state, not a broken scheduled run.
            return ($result['ok'] || !empty($result['not_configured'])) ? 0 : 1;
        }finally{$lock->release();}
    }
}
