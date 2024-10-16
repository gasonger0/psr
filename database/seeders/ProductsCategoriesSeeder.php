<?php

namespace Database\Seeders;

use App\Models\Products_categories;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::insert(`insert into products_categories(title, parent) VALUES
        ('Зефир', null),('Конфеты', null),
        ('Зефир весовой', 1),
        ('Зефир фасованный', 1),
        ('Конфеты весовые', 2),
        ('Зефир в шоколадной глазури вес', 3),
        ('Зефир в йогуртовой глазури вес', 3),
        ('Зефир в сахарной пудре вес', 3),
        ('Зефир в шоколадной глазури вес', 4),
        ('Зефир в йогуртовой глазури вес', 4),
        ('Зефир в сахарной пудре вес', 4),
        ('Ника', 4),
        ('Тверской кондитер', 4),
        ('Саше', 5),
        ('Суфле весовое в завертке', 5),
        ('Конфеты ликер.колл.', 5);`);
    }
}
