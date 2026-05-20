<?php

/** One-off script to (re)generate tours.json. Run: php database/seeders/data/build_tours_json.php */

/** @var list<array<string, mixed>> $tours */
$tours = require __DIR__ . '/tours_data.php';

$path = __DIR__ . '/tours.json';
file_put_contents($path, json_encode($tours, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo 'Wrote ' . count($tours) . " tours to {$path}\n";
