<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Models\Category;
use Illuminate\Support\Str;

class TourGenerator
{
    public function __construct(private readonly LlmClient $llm) {}

    /**
     * @return array{
     *   title:string, slug:string, summary:string, description:string,
     *   duration_days:int, category_slugs:list<string>,
     *   route_waypoints:list<array{name:string, lat:float, lng:float}>,
     *   departures:list<array{starts_on:string, ends_on:string, price_rub:int, seats_total:int}>
     * }
     */
    public function generate(string $userPrompt, ?string $categoryHint = null, ?int $durationDays = null): array
    {
        $categories = Category::query()->orderBy('position')->pluck('name', 'slug')->all();
        $catLines = collect($categories)->map(fn ($name, $slug) => "  - {$slug}: {$name}")->implode("\n");

        $system = <<<TXT
Ты помощник по составлению туристических туров для каталога. Возвращай ТОЛЬКО валидный JSON, без markdown, без объяснений.
Допустимые категории (используй только их slug-и):
{$catLines}

Схема ответа:
{
  "title": string (60-80 символов, русский),
  "slug": string (latin-kebab-case, 30-50 символов),
  "summary": string (120-200 символов),
  "description": string (Markdown, 5-7 абзацев, разделены \\n\\n; программа по дням, что включено, что взять),
  "duration_days": integer (1-21),
  "category_slugs": [string, ...] (1-3 элемента из списка выше),
  "route_waypoints": [
    {"name": string, "lat": number, "lng": number}, ...
  ] (4-8 точек, реальные географические координаты),
  "departures": [
    {"starts_on": "YYYY-MM-DD", "ends_on": "YYYY-MM-DD", "price_rub": integer, "seats_total": integer}, ...
  ] (2-4 заезда в ближайшие 6 месяцев, цены в рублях 30000-300000)
}
TXT;

        $user = trim($userPrompt);
        if ($categoryHint) {
            $user .= "\nКатегория: {$categoryHint}";
        }
        if ($durationDays) {
            $user .= "\nДлительность: {$durationDays} дней";
        }

        $data = $this->llm->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], expectsJson: true);

        $this->assertHasContent($data);

        return $this->normalize($data, array_keys($categories));
    }

    /**
     * Guard against the LLM returning `{}` or completely off-schema JSON, which would
     * otherwise silently produce an empty "Новый тур" draft.
     *
     * @param  array<string,mixed>  $raw
     */
    private function assertHasContent(array $raw): void
    {
        $title = trim((string) ($raw['title'] ?? ''));
        $description = trim((string) ($raw['description'] ?? ''));
        $summary = trim((string) ($raw['summary'] ?? ''));

        if ($title === '' || ($description === '' && $summary === '')) {
            throw new LlmException(
                'LLM вернул пустой/неполный JSON. Попробуйте другую модель или упростите промпт. '
                . 'Получено: ' . substr(json_encode($raw, JSON_UNESCAPED_UNICODE) ?: '', 0, 200)
            );
        }
    }

    /**
     * @param  array<string,mixed>  $raw
     * @param  list<string>  $validCategorySlugs
     * @return array<string,mixed>
     */
    private function normalize(array $raw, array $validCategorySlugs): array
    {
        $title = (string) ($raw['title'] ?? 'Новый тур');
        $slug = Str::slug((string) ($raw['slug'] ?? Str::slug($title) . '-' . Str::random(6)));

        return [
            'title' => $title,
            'slug' => $slug,
            'summary' => (string) ($raw['summary'] ?? ''),
            'description' => (string) ($raw['description'] ?? ''),
            'duration_days' => max(1, min(21, (int) ($raw['duration_days'] ?? 7))),
            'category_slugs' => array_values(array_intersect(
                $validCategorySlugs,
                array_map('strval', (array) ($raw['category_slugs'] ?? []))
            )),
            'route_waypoints' => $this->normalizeWaypoints($raw['route_waypoints'] ?? []),
            'departures' => $this->normalizeDepartures($raw['departures'] ?? []),
        ];
    }

    /**
     * @param  mixed  $points
     * @return list<array{name:string, lat:float, lng:float}>
     */
    private function normalizeWaypoints(mixed $points): array
    {
        if (! is_array($points)) {
            return [];
        }

        $result = [];
        foreach ($points as $p) {
            if (! is_array($p)) {
                continue;
            }
            $lat = (float) ($p['lat'] ?? 0);
            $lng = (float) ($p['lng'] ?? 0);
            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }
            $result[] = ['name' => (string) ($p['name'] ?? ''), 'lat' => $lat, 'lng' => $lng];
        }

        return array_slice($result, 0, 12);
    }

    /**
     * @param  mixed  $dep
     * @return list<array{starts_on:string, ends_on:string, price_rub:int, seats_total:int}>
     */
    private function normalizeDepartures(mixed $dep): array
    {
        if (! is_array($dep)) {
            return [];
        }
        $out = [];
        foreach ($dep as $d) {
            if (! is_array($d)) {
                continue;
            }
            $start = (string) ($d['starts_on'] ?? '');
            $end = (string) ($d['ends_on'] ?? '');
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                continue;
            }
            $out[] = [
                'starts_on' => $start,
                'ends_on' => $end,
                'price_rub' => max(0, (int) ($d['price_rub'] ?? 0)),
                'seats_total' => max(1, (int) ($d['seats_total'] ?? 12)),
            ];
        }

        return array_slice($out, 0, 6);
    }
}
