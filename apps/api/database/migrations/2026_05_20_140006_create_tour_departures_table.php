<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('RUB');
            $table->unsignedSmallInteger('seats_total')->default(20);
            $table->unsignedSmallInteger('seats_available')->default(20);
            $table->timestamps();

            $table->index(['tour_id', 'starts_on']);
            $table->index('starts_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_departures');
    }
};
