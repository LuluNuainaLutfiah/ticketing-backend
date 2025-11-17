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
        Schema::create('ticketing_messages', function (Blueprint $table) {
        $table->bigIncrements('id_message');

        $table->text('message_body');
        $table->timestamp('sent_at')->useCurrent();
        $table->boolean('read_status')->default(false);

        $table->unsignedBigInteger('id_ticket');
        $table->foreign('id_ticket')->references('id_ticket')->on('ticketing')->cascadeOnDelete();

        $table->unsignedBigInteger('id_sender');
        $table->foreign('id_sender')->references('id')->on('users')->cascadeOnDelete();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticketing_messages');
    }
};
