<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpadRenovationAiCatalogReferences extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_renovation_assets')) {
            throw new \RuntimeException('SPAD renovation assets migration must run first.');
        }
        if (!Schema::hasColumn('spad_renovation_assets', 'reference_path')) {
            Schema::table('spad_renovation_assets', function (Blueprint $table) {
                $table->string('reference_path', 255)->nullable();
            });
        }
        if (!Schema::hasColumn('spad_renovation_assets', 'material_spec')) {
            Schema::table('spad_renovation_assets', function (Blueprint $table) {
                $table->text('material_spec')->nullable();
            });
        }
    }
    public function down()
    {
        // Never erase licensed reference photos and catalog specifications on rollback.
    }
}
