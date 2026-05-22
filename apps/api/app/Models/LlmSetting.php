<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmSetting extends Model
{
    protected $fillable = [
        'provider',
        'base_url',
        'api_key',
        'model',
        'temperature',
        'max_tokens',
        'enabled',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'enabled' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'provider' => config('services.llm.provider', 'openai'),
                'base_url' => config('services.llm.base_url', 'https://api.openai.com/v1'),
                'api_key' => config('services.llm.api_key'),
                'model' => config('services.llm.model', 'gpt-4o-mini'),
                'temperature' => config('services.llm.temperature', 0.7),
                'max_tokens' => config('services.llm.max_tokens', 2048),
                'enabled' => false,
            ],
        );
    }

    public function isUsable(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // Ollama (local) does not require an API key; remote providers do.
        return $this->provider === 'ollama' || filled($this->api_key);
    }
}
