<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'beach', 'name' => 'Пляжный отдых', 'icon' => 'sun', 'position' => 1],
            ['slug' => 'mountains', 'name' => 'Горы и треккинг', 'icon' => 'mountain', 'position' => 2],
            ['slug' => 'city', 'name' => 'Городские туры', 'icon' => 'building', 'position' => 3],
            ['slug' => 'culture', 'name' => 'Культурно-познавательные', 'icon' => 'landmark', 'position' => 4],
            ['slug' => 'adventure', 'name' => 'Приключения', 'icon' => 'compass', 'position' => 5],
        ];

        foreach ($categories as $row) {
            Category::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
