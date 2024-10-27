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
            'title' => '1. Зефир',
        ]);
        Products_categories::insert([
            'title' => '2 Конфеты',
        ]);
        Products_categories::insert([
            'title' => '1.1 Зефир весовой',
            'parent' => 1
        ]);
        Products_categories::insert([
            'title' => '1.2 Зефир фасованный',
            'parent' => 1
        ]);
        Products_categories::insert([
            'title' => '2.1 Конфеты весовые',
            'parent' => 2
        ]);
        Products_categories::insert([
            'title' => '1.1.1 Зефир в шоколадной глазури вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => '1.1.2 Зефир в йогуртовой глазури вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => '1.1.3 Зефир в сахарной пудре вес',
            'parent' => 3
        ]);
        Products_categories::insert([
            'title' => '1.2.1 Зефир в шоколадной глазури фас',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => '1.2.2. Зефир в йогуртовой глаз, фас',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => '1.2.3 Зефир в сахарной пудре фасованный',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => '1.2.9.2. Ника',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => '1.2.9.5.Тверской кондитер',
            'parent' => 4
        ]);
        Products_categories::insert([
            'title' => '2.1.1 Саше',
            'parent' => 5
        ]);
        Products_categories::insert([
            'title' => '2.1.2 Суфле весовое в завертке',
            'parent' => 5
        ]);
        Products_categories::insert([
            'title' => '2.1.5 Конфеты ликер. колл.',
            'parent' => 5
        ]);

    }
}
