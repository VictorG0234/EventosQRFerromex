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
        // Para SQLite, necesitamos recrear la tabla
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TABLE prizes_new AS SELECT * FROM prizes');
            DB::statement('DROP TABLE prizes');
            DB::statement('CREATE TABLE prizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(255),
                stock INTEGER NOT NULL DEFAULT 1,
                value DECIMAL(10,2),
                image VARCHAR(255),
                active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            )');
            // Si prizes_new tiene initial_stock, excluirlo del INSERT
            DB::statement('INSERT INTO prizes (id, event_id, name, description, category, stock, value, image, active, created_at, updated_at)
                SELECT id, event_id, name, description, category, stock, value, image, active, created_at, updated_at FROM prizes_new');
            DB::statement('DROP TABLE prizes_new');
            DB::statement('CREATE INDEX prizes_event_id_category_index ON prizes(event_id, category)');
            DB::statement('CREATE INDEX prizes_event_id_active_index ON prizes(event_id, active)');
        } else {
            Schema::table('prizes', function (Blueprint $table) {
                $table->string('category')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TABLE prizes_new AS SELECT * FROM prizes');
            DB::statement('DROP TABLE prizes');
            DB::statement('CREATE TABLE prizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(255) NOT NULL,
                stock INTEGER NOT NULL DEFAULT 1,
                value DECIMAL(10,2),
                image VARCHAR(255),
                active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            )');
            // Si prizes_new tiene initial_stock, excluirlo del INSERT
            DB::statement('INSERT INTO prizes (id, event_id, name, description, category, stock, value, image, active, created_at, updated_at)
                SELECT id, event_id, name, description, category, stock, value, image, active, created_at, updated_at FROM prizes_new');
            DB::statement('DROP TABLE prizes_new');
            DB::statement('CREATE INDEX prizes_event_id_category_index ON prizes(event_id, category)');
            DB::statement('CREATE INDEX prizes_event_id_active_index ON prizes(event_id, active)');
        } else {
            Schema::table('prizes', function (Blueprint $table) {
                $table->string('category')->nullable(false)->change();
            });
        }
    }
};
