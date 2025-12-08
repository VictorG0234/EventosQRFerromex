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
            $table->boolean('prize_delivered')->default(false)->after('drawn_at');
            $table->timestamp('delivered_at')->nullable()->after('prize_delivered');
            $table->foreignId('delivered_by')->nullable()->after('delivered_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropColumn(['prize_delivered', 'delivered_at', 'delivered_by']);
        });
    }
};
