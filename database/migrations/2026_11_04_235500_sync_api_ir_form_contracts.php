<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep every API.ir service's public form synchronized with the canonical
 * official SDK contract. Never alter prices, enablement, historical requests,
 * payment records or result payloads.
 */
class SyncApiIrFormContracts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services'))
            throw new RuntimeException('Inquiry service catalog is not installed.');
        foreach (\App\Services\ApiIrInquirySchema::all() as $slug=>$fields) {
            if (!\App\Services\ApiIrClient::supports($slug))
                throw new RuntimeException('Unknown provider operation: '.$slug);
            $affected=DB::table('inquiry_services')->where('slug',$slug)->update([
                'form_schema'=>json_encode($fields,JSON_UNESCAPED_UNICODE),
                'updated_at'=>now(),
            ]);
            if ($affected===0 && !DB::table('inquiry_services')->where('slug',$slug)->exists())
                throw new RuntimeException('Missing inquiry service: '.$slug);
        }
    }

    public function down()
    {
        // Never restore invalid provider schemas. Payment / KYC records are kept.
    }
}
