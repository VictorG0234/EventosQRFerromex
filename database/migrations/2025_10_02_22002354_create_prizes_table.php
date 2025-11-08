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
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nombre del premio
            $table->text('description')->nullable(); // Descripción del premio
            $table->string('category'); // Categoría del premio (debe coincidir con premios_rifa del CSV)
            $table->integer('stock'); // Cantidad disponible
            $table->integer('initial_stock'); // Stock inicial (para estadísticas)
            $table->decimal('value', 10, 2)->nullable(); // Valor del premio (opcional)
            $table->string('image')->nullable(); // Imagen del premio
            $table->boolean('active')->default(true); // Si el premio está activo para rifas
            $table->timestamps();
            
            // Índices
            $table->index(['event_id', 'category']);
            $table->index(['event_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
