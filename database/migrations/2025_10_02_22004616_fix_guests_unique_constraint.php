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
            // Eliminar constraint único del numero_empleado
            $table->dropUnique(['numero_empleado']);
            
            // Agregar constraint único compuesto para event_id + numero_empleado
            $table->unique(['event_id', 'numero_empleado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'numero_empleado']);
            $table->unique('numero_empleado');
        });
    }
};
