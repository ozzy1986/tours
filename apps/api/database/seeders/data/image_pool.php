<?php

/**
 * Verified working Unsplash photo ids (HTTP 200 on images.unsplash.com).
 * Format: https://images.unsplash.com/photo-{id}?w=800&q=80
 *
 * @return array{
 *     all: list<string>,
 *     sea: list<int>,
 *     mountains: list<int>,
 *     city: list<int>,
 *     winter: list<int>,
 *     url: callable(string): string
 * }
 */
$ids = [
    '1506905925346-21bda4d32df4', // 0 mountains / lake
    '1464822759023-fed622ff2c3b', // 1 volcano / peak
    '1441974231531-c6227db76b6e', // 2 forest lake
    '1507525428034-b723cf961d3e', // 3 beach
    '1565008576549-57569a49371d', // 4 old town / culture
    '1544551763-46a013bb70d5', // 5 tropical sea
    '1504829857797-ddff29c27927', // 6 iceland / dramatic sky
    '1551524559-8af4e6624178', // 7 ski / snow sport
    '1500530855697-b586d89ba3ee', // 8 canyon / adventure
    '1501785888041-af3ef285b470', // 9 coast aerial
    '1470071459604-3b5ec3a7fe05', // 10 river / nature
    '1519681393784-d120267933ba', // 11 mountain lake / aurora sky
    '1472214103451-9374bd1c798e', // 12 landscape valley
    '1502602898657-3e91760cbb34', // 13 european city
    '1566073771259-6a8506099945', // 14 resort / pool
    '1571896349842-33c89424de2d', // 15 hotel terrace
    '1582719478250-c89cae4dc85b', // 16 spa / interior travel
    '1555881400-74d7acaacd8b', // 17 bridge over water
    '1454165804606-c3d57bc86b40', // 18 laptop travel / city work
    '1469474968028-56623f02e42e', // 19 valley road
    '1504674900247-0877df9cc836', // 20 food / dining travel
    '1559827260-dc66d52bef19', // 21 coffee / cafe
    '1504280390367-361c6d9f38f4', // 22 camping / outdoors
];

$url = static fn (string $id): string => "https://images.unsplash.com/photo-{$id}?w=800&q=80";

return [
    'all' => array_map($url, $ids),
    'sea' => [3, 5, 9, 14, 15, 17],
    'mountains' => [0, 1, 8, 11, 12],
    'city' => [4, 13, 18, 20, 21],
    'winter' => [6, 7, 11],
    'url' => $url,
];
