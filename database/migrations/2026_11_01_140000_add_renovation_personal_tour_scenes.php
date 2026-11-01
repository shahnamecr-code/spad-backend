<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenovationPersonalTourScenes extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_renovation_projects')) {
            throw new \RuntimeException('Create SPAD renovation projects first.');
        }
        if (!Schema::hasColumn('spad_renovation_projects','tour_scenes')) {
            Schema::table('spad_renovation_projects', function (Blueprint $table) {
                $table->json('tour_scenes')->nullable();
            });
        }
    }

    public function down()
    {
        // Keep personal tours and their project references safe during rollback.
    }
}
