<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\Category;
use App\Models\LlmSetting;
use App\Services\Llm\LlmException;
use App\Services\Llm\TourGenerator;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateLlm')
                ->label('Сгенерировать через LLM')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->visible(fn () => LlmSetting::current()->isUsable())
                ->modalHeading('Генерация тура через LLM')
                ->modalDescription('Опишите желаемый тур. Поля формы будут заполнены черновиком — проверьте и сохраните.')
                ->form([
                    Forms\Components\Textarea::make('prompt')
                        ->label('Промпт')
                        ->required()
                        ->rows(4)
                        ->placeholder('Например: 7 дней, Байкал, треккинг, июль'),
                    Forms\Components\Select::make('category_hint')
                        ->label('Категория (подсказка)')
                        ->options(Category::query()->pluck('name', 'slug')),
                    Forms\Components\TextInput::make('duration_days')
                        ->label('Длительность (дней)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(21),
                ])
                ->action(function (array $data, TourGenerator $generator): void {
                    try {
                        $result = $generator->generate(
                            $data['prompt'],
                            $data['category_hint'] ?? null,
                            isset($data['duration_days']) ? (int) $data['duration_days'] : null,
                        );
                    } catch (LlmException $e) {
                        Notification::make()->title('Ошибка LLM')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $geo = $this->waypointsToGeoJson($result['route_waypoints']);
                    $categoryIds = Category::query()
                        ->whereIn('slug', $result['category_slugs'])
                        ->pluck('id')
                        ->all();

                    $this->form->fill([
                        'title' => $result['title'],
                        'slug' => $result['slug'],
                        'summary' => $result['summary'],
                        'description' => $result['description'],
                        'duration_days' => $result['duration_days'],
                        'route_geojson' => json_encode($geo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        'categories' => $categoryIds,
                        'meta_title' => $result['title'],
                        'meta_description' => Str::limit($result['summary'], 160),
                    ]);

                    Notification::make()
                        ->title('Черновик сгенерирован')
                        ->body('Проверьте маршрут, добавьте фото и даты заездов после сохранения.')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @param  list<array{name:string, lat:float, lng:float}>  $waypoints
     * @return array<string, mixed>
     */
    private function waypointsToGeoJson(array $waypoints): array
    {
        $coords = [];
        $points = [];

        foreach ($waypoints as $wp) {
            $coords[] = [$wp['lng'], $wp['lat']];
            $points[] = $wp;
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coords,
            'waypoints' => $points,
        ];
    }
}
