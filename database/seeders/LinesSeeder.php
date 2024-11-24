<?php

namespace Database\Seeders;

use App\Models\Lines;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lines::create([
            "line_id" => 1,
            "title" => "ВАРОЧНАЯ КОЛОНКА №1",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 2,
            "title" => "ВАРОЧНАЯ КОЛОНКА №2",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 3,
            "title" => "МОНДОМИКС",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 4,
            "title" => "ТОРНАДО",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 5,
            "title" => "КИТАЙСКИЙ АЭРОС",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 6,
            "title" => "Машина для производства стаканчиков",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 7,
            "title" => "Машина для формовки Dream Kissм (ОКА)",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 8,
            "title" => "Первая фис машина",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 9,
            "title" => "Вторая фис машина",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 10,
            "title" => "Третья фис машина",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 11,
            "title" => "Непрерывная линия №1",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 12,
            "title" => "НЕПРЕРЫВНАЯ ЛИНИЯ №2",
            "type_id" => "1"
        ]);
        Lines::create([
            "line_id" => 13,
            "title" => "FLOY PAK 7 с апликатором",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 14,
            "title" => "НЕПРЕРЫВНАЯ ЛИНИЯ №2 - Шоколадная линия 2",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 15,
            "title" => "FLOY PAK 9",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 16,
            "title" => "FLOY PAK Большой китайский №4",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 17,
            "title" => "Непрерывная линия сахарной пудры №1 - Шоколадная линия",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 18,
            "title" => "Непрерывная линия сахарной пудры №1",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 19,
            "title" => "FLOY PAK 6 с апликатором",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 20,
            "title" => "Шоколадная линия 1",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 21,
            "title" => "FLOY PAK 8",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 22,
            "title" => "FLOY PAK 3",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 23,
            "title" => "FLOY PAK 1",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 24,
            "title" => "Линия эквивалент",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 25,
            "title" => "Полуавтоматическая линия сахарной пудры",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 26,
            "title" => "FLOY PAK 2",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 27,
            "title" => "FLOY PAK №10",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 28,
            "title" => "FLOY PAK №5",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 29,
            "title" => "Термоупаковка 1",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 30,
            "title" => "Термоупаковка 2",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 31,
            "title" => "Линия ONE SHOT",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 32,
            "title" => "НОВАЯ вертикальная установка",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 33,
            "title" => "СТАРАЯ вертикальная установка",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 34,
            "title" => "Маленький конш",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 35,
            "title" => "Большой конш",
            "type_id" => "2"
        ]);
        Lines::create([
            "line_id" => 36,
            "title" => "Картонажный участок",
            "type_id" => "2"
        ]);
        Lines::create([
            "title" => "Сборка ящиков под продукцию",
            "type_id" => 2
        ]);
        Lines::create([
            "title" => "Заверточная машина №1",
            "type_id" => 2
        ]);
        Lines::create([
            "title" => "Заверточная машина №2",
            "type_id" => 2
        ]);
        Lines::create([
            "title" => "Сборка подарочного набора",
            "type_id" => 2
        ]);
        Lines::create([
            "title" => "Обсыпка кокосовой стружкой",
            "type_id" => 2 
        ]);
    }
}
