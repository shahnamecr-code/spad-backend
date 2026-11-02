<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpadPropertyHandoverChecklists extends Migration
{
    public function up()
    {
        Schema::create('spad_handover_checklists', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->string('type', 24)->index();
            $t->string('status', 20)->default('draft');
            $t->string('title', 150);
            $t->string('address', 500)->nullable();
            $t->string('unit', 70)->nullable();
            $t->string('contract_reference', 100)->nullable();
            $t->string('handover_from', 150)->nullable();
            $t->string('handover_to', 150)->nullable();
            $t->date('handover_date')->nullable();
            $t->json('details')->nullable();
            $t->json('items')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'updated_at']);
        });
        Schema::create('spad_handover_photos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('checklist_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('item_key', 70)->nullable();
            $t->string('path', 450);
            $t->string('original_name', 160);
            $t->string('mime', 80);
            $t->unsignedInteger('bytes');
            $t->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('spad_handover_photos');
        Schema::dropIfExists('spad_handover_checklists');
    }
}
