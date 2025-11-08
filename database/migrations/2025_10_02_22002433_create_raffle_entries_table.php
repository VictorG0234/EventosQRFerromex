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
        Schema::create('raffle_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->foreignId('prize_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, won, lost
            $table->timestamp('participated_at'); // Momento de participación en la rifa
            $table->timestamp('drawn_at')->nullable(); // Momento en que se realizó el sorteo
            $table->json('raffle_metadata')->nullable(); // Metadatos del sorteo
            $table->timestamps();
            
            // Índices
            $table->index(['event_id', 'prize_id', 'status']);
            $table->index(['guest_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_entries');
    }
};
