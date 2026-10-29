<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInquiryContractDocuments extends Migration
{
    public function up()
    {
        Schema::create('inquiry_contract_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inquiry_request_id');
            $table->string('original_name', 190);
            $table->string('disk_path', 255)->unique();
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
            $table->foreign('inquiry_request_id')->references('id')->on('inquiry_requests')->onDelete('cascade');
            $table->index(['inquiry_request_id', 'created_at'], 'contract_documents_request_created');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiry_contract_documents');
    }
}
