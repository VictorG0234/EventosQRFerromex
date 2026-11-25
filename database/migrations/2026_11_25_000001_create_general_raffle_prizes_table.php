<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_raffle_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['event_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_raffle_prizes');
    }
};
