<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\LlmSetting;
use App\Services\Llm\LlmClient;
use App\Services\Llm\LlmException;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;

class LlmSettingsPage extends Page implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Настройки LLM';

    protected static ?string $title = 'Генерация туров (LLM)';

    protected static ?string $navigationGroup = 'Система';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'llm-settings';

    protected static string $view = 'filament.pages.llm-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $s = LlmSetting::current();
        $this->form->fill([
            'provider' => $s->provider,
            'base_url' => $s->base_url,
            'api_key' => $s->api_key ? '********' : null,
            'model' => $s->model,
            'temperature' => $s->temperature,
            'max_tokens' => $s->max_tokens,
            'enabled' => $s->enabled,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Подключение')
                    ->description('OpenAI-compatible API (OpenAI, Ollama, LM Studio). Ключ хранится зашифрованным в БД.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')->label('Включить LLM-генерацию'),
                        Forms\Components\Select::make('provider')
                            ->label('Провайдер')
                            ->options([
                                'openai' => 'OpenAI',
                                'ollama' => 'Ollama (локально)',
                                'custom' => 'Другой',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('base_url')
                            ->label('Base URL')
                            ->url()
                            ->required()
                            ->default('https://api.openai.com/v1'),
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Оставьте ******** чтобы не менять существующий ключ'),
                        Forms\Components\TextInput::make('model')
                            ->label('Модель')
                            ->required()
                            ->default('gpt-4o-mini'),
                        Forms\Components\TextInput::make('temperature')
                            ->label('Температура')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->step(0.1)
                            ->default(0.7),
                        Forms\Components\TextInput::make('max_tokens')
                            ->label('Макс. токенов')
                            ->numeric()
                            ->minValue(256)
                            ->maxValue(8192)
                            ->default(2048),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->submit('save'),
            Action::make('test')
                ->label('Проверить подключение')
                ->color('gray')
                ->action('testConnection'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $setting = LlmSetting::current();

        $key = $data['api_key'] ?? null;
        if (blank($key) || $key === '********') {
            unset($data['api_key']);
        }

        $setting->update($data);

        Notification::make()->title('Настройки сохранены')->success()->send();
    }

    public function testConnection(LlmClient $client): void
    {
        $this->save();

        try {
            $reply = $client->ping();
            Notification::make()
                ->title('Подключение успешно')
                ->body('Ответ: ' . $reply)
                ->success()
                ->send();
        } catch (LlmException $e) {
            Notification::make()
                ->title('Ошибка LLM')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
