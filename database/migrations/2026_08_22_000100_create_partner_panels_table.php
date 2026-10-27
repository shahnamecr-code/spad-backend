<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerPanelsTable extends Migration
{
    public function up()
    {
        Schema::create('partner_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type', 50)->index();
            $table->string('status', 20)->default('active')->index();
            $table->string('title')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');
            $table->unique(['user_id', 'type'], 'partner_panels_user_type_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('partner_panels');
    }
}
