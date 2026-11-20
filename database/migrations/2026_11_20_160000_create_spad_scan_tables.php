<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadScanTables extends Migration
{
    public function up()
    {
        Schema::create('spad_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('renovation_project_id')->nullable();
            $table->string('title', 120);
            $table->json('rooms');
            $table->json('material_ids')->nullable();
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('room_count');
            $table->unsignedTinyInteger('photo_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('spad_scan_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('spad_scans')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('path');
            $table->string('mime_type', 32);
            $table->unsignedInteger('size_bytes');
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_scan_photos');
        Schema::dropIfExists('spad_scans');
    }
}
