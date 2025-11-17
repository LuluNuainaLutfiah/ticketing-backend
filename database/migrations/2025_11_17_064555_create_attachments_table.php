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
         Schema::create('attachments', function (Blueprint $table) {
        $table->bigIncrements('id_attachment');

        $table->string('file_name');
        $table->string('file_type', 100);
        $table->string('file_path');
        $table->timestamp('uploaded_at')->useCurrent();

        $table->unsignedBigInteger('id_ticket');
        $table->foreign('id_ticket')->references('id_ticket')->on('ticketing')->cascadeOnDelete();

        $table->unsignedBigInteger('uploaded_by');
        $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();

        $table->unsignedBigInteger('id_message')->nullable();
        $table->foreign('id_message')->references('id_message')->on('ticketing_messages')->nullOnDelete();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
