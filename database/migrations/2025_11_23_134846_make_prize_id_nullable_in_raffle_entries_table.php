<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite no soporta ALTER TABLE MODIFY COLUMN, necesitamos recrear la tabla
            DB::statement('PRAGMA foreign_keys=OFF');
            
            // Crear tabla temporal con la nueva estructura
            DB::statement('
                CREATE TABLE raffle_entries_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    event_id INTEGER NOT NULL,
                    guest_id INTEGER NOT NULL,
                    prize_id INTEGER NULL,
                    status VARCHAR(255) NOT NULL DEFAULT \'pending\',
                    participated_at TIMESTAMP NOT NULL,
                    drawn_at TIMESTAMP NULL,
                    raffle_metadata TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
                    FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE CASCADE
                )
            ');
            
            // Copiar datos existentes
            DB::statement('
                INSERT INTO raffle_entries_new 
                (id, event_id, guest_id, prize_id, status, participated_at, drawn_at, raffle_metadata, created_at, updated_at)
                SELECT id, event_id, guest_id, prize_id, status, participated_at, drawn_at, raffle_metadata, created_at, updated_at
                FROM raffle_entries
            ');
            
            // Eliminar tabla antigua
            DB::statement('DROP TABLE raffle_entries');
            
            // Renombrar tabla nueva
            DB::statement('ALTER TABLE raffle_entries_new RENAME TO raffle_entries');
            
            // Recrear índices
            DB::statement('CREATE INDEX raffle_entries_event_id_prize_id_status_index ON raffle_entries(event_id, prize_id, status)');
            DB::statement('CREATE INDEX raffle_entries_guest_id_event_id_index ON raffle_entries(guest_id, event_id)');
            
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            // Para MySQL, PostgreSQL, etc.
            Schema::table('raffle_entries', function (Blueprint $table) {
                $table->dropForeign(['prize_id']);
            });

            Schema::table('raffle_entries', function (Blueprint $table) {
                $table->foreignId('prize_id')->nullable()->change();
            });

            Schema::table('raffle_entries', function (Blueprint $table) {
                $table->foreign('prize_id')->references('id')->on('prizes')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero eliminar registros con prize_id null si existen
        DB::table('raffle_entries')->whereNull('prize_id')->delete();

        // Eliminar la restricción de clave foránea
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropForeign(['prize_id']);
        });

        // Modificar la columna para hacerla no nullable
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->foreignId('prize_id')->nullable(false)->change();
        });

        // Volver a agregar la restricción de clave foránea
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->foreign('prize_id')->references('id')->on('prizes')->onDelete('cascade');
        });
    }
};
