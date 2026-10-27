<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPartnerWalletResultAndCorrectionToInquiries extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inquiry_services') && !Schema::hasColumn('inquiry_services', 'partner_payout')) {
            Schema::table('inquiry_services', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_payout')->default(0)->after('price');
            });
        }

        if (!Schema::hasTable('partner_wallets')) {
            Schema::create('partner_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_panel_id')->unique();
                $table->unsignedBigInteger('balance')->default(0);
                $table->string('status', 20)->default('active')->index();
                $table->timestamps();

                $table->foreign('partner_panel_id')->references('id')->on('partner_panels')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('partner_wallet_transactions')) {
            Schema::create('partner_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_wallet_id')->index();
                $table->unsignedBigInteger('partner_panel_id')->index();
                $table->unsignedBigInteger('inquiry_request_id')->nullable()->index();
                $table->string('direction', 20)->default('credit')->index();
                $table->string('type', 60)->default('inquiry_commission')->index();
                $table->unsignedBigInteger('amount');
                $table->unsignedBigInteger('balance_before')->default(0);
                $table->unsignedBigInteger('balance_after')->default(0);
                $table->string('status', 20)->default('completed')->index();
                $table->string('reference', 190)->unique();
                $table->string('title', 190)->nullable();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('partner_wallet_id')->references('id')->on('partner_wallets')->onDelete('cascade');
                $table->foreign('partner_panel_id')->references('id')->on('partner_panels')->onDelete('cascade');
                $table->foreign('inquiry_request_id')->references('id')->on('inquiry_requests')->onDelete('set null');
            });
        }

        if (Schema::hasTable('inquiry_requests')) {
            if (!Schema::hasColumn('inquiry_requests', 'partner_payout_amount')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('partner_payout_amount')->default(0)->after('amount');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'partner_payout_transaction_id')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('partner_payout_transaction_id')->nullable()->index()->after('wallet_transaction_id');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'result_file_path')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->string('result_file_path', 500)->nullable()->after('result_text');
                    $table->string('result_file_name', 255)->nullable()->after('result_file_path');
                    $table->string('result_file_mime', 120)->nullable()->after('result_file_name');
                    $table->unsignedBigInteger('result_file_size')->nullable()->after('result_file_mime');
                });
            }
            if (!Schema::hasColumn('inquiry_requests', 'correction_note')) {
                Schema::table('inquiry_requests', function (Blueprint $table) {
                    $table->text('correction_note')->nullable()->after('partner_note');
                    $table->timestamp('correction_requested_at')->nullable()->after('correction_note');
                    $table->timestamp('correction_resubmitted_at')->nullable()->after('correction_requested_at');
                });
            }
        }

        // Existing status column is an ENUM in the original schema. Add the correction state safely.
        if (Schema::hasTable('inquiry_requests')) {
            DB::statement("ALTER TABLE inquiry_requests MODIFY status ENUM('pending_payment','waiting_operator','processing','needs_correction','completed','failed','rejected','cancelled') NOT NULL DEFAULT 'pending_payment'");
        }

        // Snapshot the current private partner payout on already-paid unfinished manual requests.
        if (Schema::hasTable('inquiry_requests') && Schema::hasTable('inquiry_services') &&
            Schema::hasColumn('inquiry_requests', 'partner_payout_amount') && Schema::hasColumn('inquiry_services', 'partner_payout')) {
            DB::statement("UPDATE inquiry_requests ir INNER JOIN inquiry_services s ON s.id = ir.inquiry_service_id SET ir.partner_payout_amount = LEAST(COALESCE(s.partner_payout,0), ir.amount) WHERE s.execution_type = 'manual' AND ir.partner_payout_amount = 0 AND ir.payment_status = 'paid' AND ir.status <> 'completed'");
        }
    }

    public function down()
    {
        // Non-destructive feature migration: financial ledgers and inquiry results are intentionally preserved.
    }
}
