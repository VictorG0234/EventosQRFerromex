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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('apellido_p');
            $table->string('apellido_m');
            $table->string('numero_empleado')->unique();
            $table->string('area_laboral');
            $table->json('premios_rifa'); // Array de categorías de premios a los que puede acceder
            $table->string('qr_code')->unique(); // Código QR único
            $table->string('email')->nullable();
            $table->boolean('email_sent')->default(false); // Para controlar si ya se envió el QR por email
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index(['event_id', 'numero_empleado']);
            $table->index('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
