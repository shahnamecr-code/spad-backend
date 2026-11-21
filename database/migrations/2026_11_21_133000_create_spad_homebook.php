<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadHomebook extends Migration
{
    public function up(): void
    {
        Schema::create('spad_homebook_homes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 100);
            $table->timestamps();
        });
        Schema::create('spad_homebook_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('spad_homebook_homes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 24);
            $table->string('brand', 80)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('serial')->nullable();
            $table->string('room', 100)->nullable();
            $table->string('service_company', 100)->nullable();
            $table->date('purchased_on')->nullable();
            $table->unsignedBigInteger('price_toman')->nullable();
            $table->date('warranty_started_on')->nullable();
            $table->date('warranty_ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('spad_homebook_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('spad_homebook_devices')->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('kind', 24);
            $table->string('mime', 40);
            $table->string('storage_path', 180);
            $table->timestamps();
        });
        Schema::create('spad_homebook_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('spad_homebook_devices')->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('kind', 16);
            $table->date('service_on');
            $table->dateTime('completed_at')->nullable();
            $table->string('technician', 120)->nullable();
            $table->unsignedBigInteger('cost_toman')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spad_homebook_maintenance');
        Schema::dropIfExists('spad_homebook_documents');
        Schema::dropIfExists('spad_homebook_devices');
        Schema::dropIfExists('spad_homebook_homes');
    }
}
