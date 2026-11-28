<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadPublicSiteVisitorsTable extends Migration
{
    public function up()
    {
        Schema::create('spad_public_site_visitors', function (Blueprint $table) {
            $table->date('visit_day');
            $table->char('browser_hash', 64);
            $table->primary(['visit_day', 'browser_hash'], 'spad_public_site_visitor_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_public_site_visitors');
    }
}
