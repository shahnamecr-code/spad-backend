<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ApiIrInquirySchema;

/**
 * Correct previously seeded/edited forms without changing any price,
 * activation, historical request, wallet ledger or provider credential.
 */
class SyncApiIrInquiryFormSchemas extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services'))
            throw new RuntimeException('Inquiry catalog is not installed.');
        $changed=0;
        foreach (ApiIrInquirySchema::all() as $slug=>$fields) {
            $row=DB::table('inquiry_services')->where('slug',$slug)->first();
            if (!$row) throw new RuntimeException('Missing API.ir inquiry: '.$slug);
            $desired=json_encode($fields,JSON_UNESCAPED_UNICODE);
            $current=json_decode((string)$row->form_schema,true);
            if ($current===$fields)continue;
            DB::table('inquiry_services')->where('id',$row->id)->update([
                'form_schema'=>$desired,'updated_at'=>now()]);
            $changed++;
        }
        echo "APIIR_FORM_SCHEMA_SYNC_COUNT=".$changed.PHP_EOL;
    }

    public function down()
    {
        // Previously incorrect forms must not be reinstated, and historical
        // paid requests and administrator-defined service prices remain intact.
    }
}
