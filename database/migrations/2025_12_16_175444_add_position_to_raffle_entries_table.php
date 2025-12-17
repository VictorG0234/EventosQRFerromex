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
        Schema::table('raffle_entries', function (Blueprint $table) {
            // Posición del ganador en la rifa general (1, 2, 3, etc.)
            // NULL para entradas que no son ganadoras o que no tienen posición asignada
            $table->unsignedInteger('position')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
