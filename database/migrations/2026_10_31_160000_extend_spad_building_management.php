<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExtendSpadBuildingManagement extends Migration
{
    public function up()
    {
        // Buildings created by administrators may have no assigned manager initially.
        DB::statement('ALTER TABLE spad_buildings MODIFY owner_user_id BIGINT UNSIGNED NULL');
        Schema::table('spad_buildings', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_panel_id')->nullable()->index();
        });
        Schema::table('spad_building_units', function (Blueprint $table) {
            $table->smallInteger('floor_number')->nullable();
            $table->string('unit_number', 30)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('owner_name', 180)->nullable();
            $table->string('owner_phone', 20)->nullable();
            $table->boolean('is_rented')->default(false);
            $table->string('tenant_first_name', 100)->nullable();
            $table->string('tenant_last_name', 100)->nullable();
            $table->string('tenant_phone', 20)->nullable();
            $table->date('tenant_move_out_date')->nullable();
        });
        Schema::create('spad_building_bills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id')->index();
            $table->string('kind', 25);
            $table->string('title', 180);
            $table->unsignedBigInteger('amount_toman');
            $table->string('bill_reference', 100)->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 18)->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('spad_building_bills');
        Schema::table('spad_building_units', function (Blueprint $table) {
            $table->dropColumn(['floor_number','unit_number','postal_code','owner_name','owner_phone',
                'is_rented','tenant_first_name','tenant_last_name','tenant_phone','tenant_move_out_date']);
        });
        Schema::table('spad_buildings', function (Blueprint $table) {
            $table->dropColumn('partner_panel_id');
        });
        // Keep the optional owner assignment safe during rollback.
    }
}
