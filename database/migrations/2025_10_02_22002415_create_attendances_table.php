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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->timestamp('scanned_at'); // Momento exacto del escaneo
            $table->string('scanned_by')->nullable(); // Usuario que escaneó (opcional)
            $table->json('scan_metadata')->nullable(); // Metadatos adicionales del escaneo
            $table->timestamps();
            
            // Un invitado solo puede ser registrado una vez por evento
            $table->unique(['event_id', 'guest_id']);
            
            // Índices para estadísticas en tiempo real
            $table->index(['event_id', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
