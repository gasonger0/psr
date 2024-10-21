<?php

namespace Database\Seeders;

use App\Models\Products_categories;
use Illuminate\Database\Seeder;

class ProductsCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {  
        Products_categories::insert([
            'title' => 'Зефир',
        ]);
        Products_categories::insert([
            'title' => 'Конфеты',
        ]);
        Products_categories::insert([
            'title' => 'Зефир весовой',
            'parent' => 1
        ]);
        Products_categories::insert([
            'title' => 'Зефир фасованный',
            'parent' => 1
        ]);
        Products_categories::insert([
            'title' => 'Конфеты весовые',
            'parent' => 2
        ]);
        Products_categories::insert([
            'title' => 'Зефир в шоколадной глазури вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => 'Зефир в йогуртовой глазури вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => 'Зефир в сахарной пудре вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => 'Зефир в шоколадной глазури вес',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => 'Зефир в йогуртовой глазури вес',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => 'Зефир в сахарной пудре вес',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => 'Ника',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => 'Тверской кондитер',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => 'Саше',
            'parent' => 5
        ]);
        Products_categories::insert([
            'title' => 'Суфле весовое в завертке',
            'parent' => 5
        ]);
        Products_categories::insert([
            'title' => 'Конфеты ликер.колл.',
            'parent' => 5
        ]);

    }
}
