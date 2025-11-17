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
        Schema::create('activity_log', function (Blueprint $table) {
        $table->bigIncrements('id_log');

        $table->string('action');
        $table->text('details')->nullable();
        $table->timestamp('action_time')->useCurrent();

        $table->unsignedBigInteger('performed_by');
        $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();

        $table->unsignedBigInteger('id_ticket');
        $table->foreign('id_ticket')->references('id_ticket')->on('ticketing')->cascadeOnDelete();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
