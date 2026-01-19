<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticketing', function (Blueprint $table) {
        $table->bigIncrements('id_ticket');
        $table->string('code_ticket')->unique();
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('category');
        $table->enum('priority', ['LOW','MEDIUM','HIGH'])->default('MEDIUM');
        $table->enum('status', ['OPEN','IN_REVIEW','IN_PROGRESS','RESOLVED'])->default('OPEN');

        $table->unsignedBigInteger('created_by');
        $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

        $table->timestamp('resolved_at')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticketing');
    }
};
