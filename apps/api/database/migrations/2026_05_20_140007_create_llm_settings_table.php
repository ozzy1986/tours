<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->default('openai');
            $table->string('base_url')->default('https://api.openai.com/v1');
            $table->text('api_key')->nullable(); // encrypted via Eloquent cast
            $table->string('model')->default('gpt-4o-mini');
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->unsignedSmallInteger('max_tokens')->default(2048);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_settings');
    }
};
