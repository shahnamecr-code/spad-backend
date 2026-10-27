<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairPartnerInquiryRuntimeSchema extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inquiry_services') && !Schema::hasColumn('inquiry_services', 'partner_handler_type')) {
            Schema::table('inquiry_services', function (Blueprint $table) {
                $table->string('partner_handler_type', 60)->nullable()->index()->after('execution_type');
            });
        }

        if (Schema::hasTable('inquiry_requests')) {
            if (!Schema::hasColumn('inquiry_requests', 'assigned_partner_panel_id')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('assigned_partner_panel_id')->nullable()->index()->after('assigned_admin_id');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'partner_assigned_at')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->timestamp('partner_assigned_at')->nullable()->after('assigned_partner_panel_id');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'partner_accepted_at')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->timestamp('partner_accepted_at')->nullable()->after('partner_assigned_at');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'partner_note')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->text('partner_note')->nullable()->after('operator_note');
                });
            }
        }

        if (!Schema::hasTable('inquiry_partner_routing_rules')) {
            Schema::create('inquiry_partner_routing_rules', function (Blueprint $table) {
                $table->id();
                $table->string('partner_type', 60)->index();
                $table->unsignedTinyInteger('weekday')->index();
                $table->string('scope_key', 80)->default('all');
                $table->unsignedBigInteger('inquiry_service_id')->nullable()->index();
                $table->unsignedBigInteger('partner_panel_id')->index();
                $table->boolean('enabled')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->unique(['partner_type', 'weekday', 'scope_key'], 'inq_partner_route_unique');
            });
        }
    }

    public function down()
    {
        // Repair migration is intentionally non-destructive.
    }
}
