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
            // Índice para nivel_de_puesto (usado en dashboard)
            $table->index('nivel_de_puesto');
            
            // Índice para puesto (usado en reporte de estadísticas)
            $table->index('puesto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['nivel_de_puesto']);
            $table->dropIndex(['puesto']);
        });
    }
};
