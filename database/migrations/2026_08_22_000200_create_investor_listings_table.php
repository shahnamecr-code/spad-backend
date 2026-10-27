<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestorListingsTable extends Migration
{
    public function up()
    {
        Schema::create('investor_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id')->unique();
            $table->unsignedBigInteger('submitted_by_partner_panel_id')->nullable();
            $table->string('source', 20)->default('admin');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('market_price')->nullable();
            $table->unsignedBigInteger('investor_price')->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('investment_score')->nullable();
            $table->unsignedSmallInteger('investment_horizon_months')->nullable();
            $table->text('deal_summary')->nullable();
            $table->text('risk_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('advertisement_id')->references('id')->on('advertisements')->onDelete('cascade');
            $table->foreign('submitted_by_partner_panel_id')->references('id')->on('partner_panels')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');
            $table->index(['status', 'published_at']);
            $table->index(['source', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('investor_listings');
    }
}
