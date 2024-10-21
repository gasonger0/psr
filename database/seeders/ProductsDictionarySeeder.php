<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductsDictionary;

class ProductsDictionarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductsDictionary::insert([
            'title' => 'Зефир "Жизель"  с "вареной сгущенкой" глазированный в цвет. индивид. упаковке 4кг',
            'category_id' => 6
        ]);
        ProductsDictionary::insert([
            'title' => 'Зефир "Жизель"  с вареной сгущенкой глазированный с ароматом ванили 2,5кг',
            'category_id' => 6
        ]);
        ProductsDictionary::insert([
            'title' => 'Зефир "Жизель" в йогуртовой глазури с ароматом ванили 2,5кг',
            'category_id' => 7
        ]);
    }
}
