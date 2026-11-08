<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyAcceptanceRequests extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_property_acceptance_requests')) {
            Schema::create('spad_property_acceptance_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('quote_hash', 64)->unique();
                $table->text('postal_code_encrypted');
                $table->text('address_encrypted')->nullable();
                $table->string('province', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('property_kind', 32);
                $table->decimal('area_m2', 10, 2);
                $table->unsignedBigInteger('estimated_total_toman');
                $table->unsignedBigInteger('range_low_toman')->nullable();
                $table->unsignedBigInteger('range_high_toman')->nullable();
                $table->string('market_source', 100);
                $table->string('coverage', 32)->nullable();
                $table->text('valuation_snapshot_encrypted');
                $table->boolean('owner_confirmed')->default(false);
                $table->boolean('contact_consent')->default(false);
                $table->string('status', 25)->default('new')->index();
                $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
                $table->timestamp('follow_up_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
                $table->index(['city', 'created_at']);
            });
        }
        if (!Schema::hasTable('spad_property_acceptance_activity')) {
            Schema::create('spad_property_acceptance_activity', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('request_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action', 40);
                $table->string('from_status', 25)->nullable();
                $table->string('to_status', 25)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down()
    {
        // Retain personal service requests and their audit trail on rollback;
        // removal requires an explicit retention/deletion decision.
    }
}
