<?php

namespace Database\Seeders;

use App\Models\ProductsCategories;
use App\Models\ProductsDictionary;
use Illuminate\Database\Seeder;

class ProductsCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductsCategories::insert([
            'title' => '1. Зефир',
        ]);
        ProductsCategories::insert([
            'title' => '2. Конфеты',
        ]);
        ProductsCategories::insert([
            'title' => '1.1. Зефир весовой',
            'parent' => 1
        ]);
        ProductsCategories::insert([
            'title' => '1.2. Зефир фасованный',
            'parent' => 1
        ]);
        ProductsCategories::insert([
            'title' => '2.1. Конфеты весовые',
            'parent' => 2
        ]);
        ProductsCategories::insert([
            'title' => '1.1.1. Зефир в шоколадной глазури вес',
            'parent' => 3
        ]);
        ProductsCategories::insert([
            'title' => '1.1.2. Зефир в йогуртовой глазури вес',
            'parent' => 3
        ]);
        ProductsCategories::insert([
            'title' => '1.1.3. Зефир в сахарной пудре вес',
            'parent' => 3
        ]);
        ProductsCategories::insert([
            'title' => '1.2.1. Зефир в шоколадной глазури фас',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.2. Зефир в йогуртовой глаз, фас',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.3. Зефир в сахарной пудре фасованный',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.5. Ника',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.8.Тверской кондитер',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '2.1.1 Саше',
            'parent' => 5
        ]);
        ProductsCategories::insert([
            'title' => '2.1.2. Суфле весовое в завертке',
            'parent' => 5
        ]);
        ProductsCategories::insert([
            'title' => '2.1.3. Конфеты ликер. колл.',
            'parent' => 5
        ]);
        ProductsCategories::insert([
            'title' => '2.2. Конфеты фасованные',
            'parent' => 2
        ]);
        ProductsCategories::insert([
            'title' => '2.2.1. Розы',
            'parent' => 17
        ]);
        ProductsCategories::insert([
            'title' => '2.2.2. Подарки',
            'parent' => 17
        ]);
        ProductsCategories::insert([
            'title' => '2.2.3. Ликерная коллекция',
            'parent' => 17
        ]);
        ProductsCategories::insert([
            'title' => '1.1.4. Вкусное дело',
            'parent' => 3
        ]);
        ProductsCategories::insert([
            'title' => '1.2.4. Юнифуд',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.6. ВитаПродСервис',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.2.7. Молдавия',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.3. Вайлдберриз',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.4. Озон',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '1.5. Яндекс',
            'parent' => 4
        ]);
        ProductsCategories::insert([
            'title' => '3. Крем-десерт'
        ]);
        ProductsCategories::insert([
            'title' => '4. Архив',
        ]);
    }
}
