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
        Schema::table('guests', function (Blueprint $table) {
            // Eliminar el índice único global de numero_empleado
            // Esto permite que el mismo numero_empleado exista en diferentes eventos
            $table->dropUnique(['numero_empleado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Restaurar el índice único global (aunque no debería ser necesario)
            $table->unique('numero_empleado');
        });
    }
};
