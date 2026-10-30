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
            $result=(new MaterialPricesController())->sync();
            $this->info(($result['ok']?'SYNC_OK':'SYNC_NOT_CONNECTED').': '.($result['message']??''));
            return $result['ok']?0:1;
        }finally{$lock->release();}
    }
}
