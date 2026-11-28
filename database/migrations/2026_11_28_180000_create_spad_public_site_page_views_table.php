<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadPublicSitePageViewsTable extends Migration
{
    public function up()
    {
        Schema::create('spad_public_site_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('event_key');
            $table->string('page', 80);
            $table->unsignedBigInteger('property_id')->nullable();
            $table->dateTime('created_at');
            $table->unique(['user_id', 'event_key'], 'spad_page_view_event');
            $table->index(['user_id', 'created_at', 'id'], 'spad_page_view_user_time');
            $table->index(['created_at', 'user_id'], 'spad_page_view_time_user');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_public_site_page_views');
    }
}
