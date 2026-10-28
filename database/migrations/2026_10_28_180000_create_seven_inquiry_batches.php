<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSevenInquiryBatches extends Migration
{
    public function up()
    {
        Schema::create('inquiry_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('idempotency_key', 64);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status', 30)->default('processing');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'inquiry_batch_user_key_unique');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('inquiry_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_batch_id')->nullable()->index();
            $table->foreign('inquiry_batch_id')->references('id')->on('inquiry_batches')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('inquiry_requests', function (Blueprint $table) {
            $table->dropForeign(['inquiry_batch_id']);
            $table->dropColumn('inquiry_batch_id');
        });
        Schema::dropIfExists('inquiry_batches');
    }
}
