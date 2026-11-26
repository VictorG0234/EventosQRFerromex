<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->foreignId('general_raffle_prize_id')
                ->nullable()
                ->unique()
                ->constrained('general_raffle_prizes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('general_raffle_prize_id');
        });
    }
};
