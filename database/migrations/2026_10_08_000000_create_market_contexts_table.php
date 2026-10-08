<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('observed_on');
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->string('weather_summary')->nullable();
            $table->decimal('inflation_rate', 5, 2)->nullable();
            $table->string('inflation_region')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'observed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_contexts');
    }
};
