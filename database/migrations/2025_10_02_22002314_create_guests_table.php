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
            $table->string('compania');
            $table->string('numero_empleado')->unique();
            $table->string('nombre_completo');
            $table->string('correo')->nullable();
            $table->string('puesto');
            $table->string('nivel_de_puesto')->nullable();
            $table->string('localidad');
            $table->date('fecha_alta')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('categoria_rifa')->nullable();
            $table->string('qr_code')->unique(); // Código QR único
            $table->string('qr_code_path')->nullable(); // Ruta al archivo QR
            $table->boolean('email_sent')->default(false); // Para controlar si ya se envió el QR por email
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->unique(['event_id', 'numero_empleado']); // Unique constraint compuesto
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
