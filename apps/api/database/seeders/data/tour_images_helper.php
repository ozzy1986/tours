<?php

/**
 * Assign cover_url and photos for seed tours from image_pool.php.
 *
 * @param  list<array<string, mixed>>  $tours
 * @return list<array<string, mixed>>
 */
function assign_tour_images(array $tours): array
{
    /** @var array{all: list<string>, sea: list<int>, mountains: list<int>, city: list<int>, winter: list<int>} $pool */
    $pool = require __DIR__ . '/image_pool.php';
    $all = $pool['all'];
    $n = count($all);

    $pick = static function (array $indices) use ($all): array {
        return array_values(array_unique(array_map(fn (int $i) => $all[$i], $indices)));
    };

    foreach ($tours as $index => &$tour) {
        $slug = (string) ($tour['slug'] ?? '');
        $offsets = match (true) {
            str_contains($slug, 'crimea') => [3, 5, 9, 14, 17],
            str_contains($slug, 'sochi') || str_contains($slug, 'turkey') || str_contains($slug, 'antalya') || str_contains($slug, 'astrakhan') => [3, 5, 9, 14, 15],
            str_contains($slug, 'baikal') => [0, 11, 10, 12, 2],
            str_contains($slug, 'altai') || str_contains($slug, 'kamchatka') || str_contains($slug, 'elbrus') || str_contains($slug, 'dagestan') => [0, 1, 8, 12, 19],
            str_contains($slug, 'ski') || str_contains($slug, 'sheregesh') => [7, 1, 0, 11],
            str_contains($slug, 'spb') || str_contains($slug, 'moscow') || str_contains($slug, 'kazan') || str_contains($slug, 'golden') || str_contains($slug, 'novgorod') => [13, 4, 18, 20, 21],
            str_contains($slug, 'iceland') || str_contains($slug, 'murmansk') => [6, 11, 10, 7],
            str_contains($slug, 'georgia') => [4, 20, 13, 12],
            str_contains($slug, 'jordan') => [8, 5, 4, 1],
            str_contains($slug, 'karelia') => [2, 10, 11, 19],
            str_contains($slug, 'kaliningrad') || str_contains($slug, 'vladivostok') || str_contains($slug, 'sakhalin') => [17, 9, 3, 13],
            str_contains($slug, 'yakutia') => [6, 7, 11, 10],
            default => [
                ($index * 3) % $n,
                ($index * 3 + 1) % $n,
                ($index * 3 + 2) % $n,
                ($index * 3 + 3) % $n,
                ($index * 3 + 4) % $n,
            ],
        };

        if (! str_contains($slug, 'crimea') && ! str_contains($slug, 'sochi') && ! str_contains($slug, 'turkey')
            && ! str_contains($slug, 'antalya') && ! str_contains($slug, 'astrakhan')
            && ! str_contains($slug, 'baikal') && ! str_contains($slug, 'altai')
            && ! str_contains($slug, 'kamchatka') && ! str_contains($slug, 'elbrus')
            && ! str_contains($slug, 'dagestan') && ! str_contains($slug, 'ski')
            && ! str_contains($slug, 'sheregesh') && ! str_contains($slug, 'spb')
            && ! str_contains($slug, 'moscow') && ! str_contains($slug, 'kazan')
            && ! str_contains($slug, 'golden') && ! str_contains($slug, 'novgorod')
            && ! str_contains($slug, 'iceland') && ! str_contains($slug, 'murmansk')
            && ! str_contains($slug, 'georgia') && ! str_contains($slug, 'jordan')
            && ! str_contains($slug, 'karelia') && ! str_contains($slug, 'kaliningrad')
            && ! str_contains($slug, 'vladivostok') && ! str_contains($slug, 'sakhalin')
            && ! str_contains($slug, 'yakutia')) {
            $photos = [];
            foreach ($offsets as $o) {
                $photos[] = $all[$o % $n];
            }
        } else {
            $photos = $pick($offsets);
        }

        $tour['cover_url'] = $photos[0];
        $tour['photos'] = array_slice($photos, 0, min(6, count($photos)));
    }
    unset($tour);

    return $tours;
}
