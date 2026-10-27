<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerRoutingToInquiries extends Migration
{
    public function up()
    {
        Schema::table('inquiry_services', function (Blueprint $table) {
            $table->string('partner_handler_type', 60)->nullable()->index()->after('execution_type');
        });

        Schema::table('inquiry_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_partner_panel_id')->nullable()->index()->after('assigned_admin_id');
            $table->timestamp('partner_assigned_at')->nullable()->after('assigned_partner_panel_id');
            $table->timestamp('partner_accepted_at')->nullable()->after('partner_assigned_at');
            $table->text('partner_note')->nullable()->after('operator_note');

            $table->foreign('assigned_partner_panel_id')
                ->references('id')->on('partner_panels')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('inquiry_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_partner_panel_id']);
            $table->dropColumn([
                'assigned_partner_panel_id',
                'partner_assigned_at',
                'partner_accepted_at',
                'partner_note',
            ]);
        });

        Schema::table('inquiry_services', function (Blueprint $table) {
            $table->dropColumn('partner_handler_type');
        });
    }
}
