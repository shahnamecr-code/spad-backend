<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMarketTickerSettings extends Migration
{
    public function up()
    {
        $defaults = [
            'market_ticker_enabled' => '1',
            'market_ticker_api_url' => 'https://api.navasan.tech/latest/',
            'market_ticker_api_key' => '',
            // Navasan free plan refreshes every two hours. Paid plans can safely lower this in admin.
            'market_ticker_refresh_seconds' => '7200',
            'market_ticker_usd' => '1',
            'market_ticker_gold_18' => '1',
            'market_ticker_gold_24' => '1',
            'market_ticker_new_coin' => '1',
            'market_ticker_old_coin' => '1',
            'market_ticker_half_coin' => '1',
            'market_ticker_quarter_coin' => '1',
            'market_ticker_gold_ounce' => '1',
            'market_ticker_melted_gold' => '1',
            'market_ticker_mesghal_gold' => '1',
            'market_ticker_eur' => '1',
            'market_ticker_gbp' => '1',
            'market_ticker_usdt' => '1',
            'market_ticker_aed' => '1',
            'market_ticker_try' => '1',
        ];

        foreach ($defaults as $code => $value) {
            DB::table('settings')->updateOrInsert(
                ['group' => 'market_ticker', 'code' => $code],
                ['text_value' => $value]
            );
        }
    }

    public function down()
    {
        DB::table('settings')
            ->where('group', 'market_ticker')
            ->delete();
    }
}
