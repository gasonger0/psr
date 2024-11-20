<?php

namespace Database\Seeders;

use App\Models\Responsible;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResponsibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Responsible::create([
            'title' => 'Иванова Алена Сергеева',
            'position' => 1
        ]);
        Responsible::create([
            'title' => 'Чинякина Светлана Викторовна',
            'position' => 1
        ]);
        Responsible::create([
            'title' => 'Жукова Марина Петровна',
            'position' => 2
        ]);
        Responsible::create([
            'title' => 'Степанова Людмила Вячеславовна',
            'position' => 2
        ]);
        Responsible::create([
            'title' => 'Васильева Светлана Валерьевна',
            'position' => 2
        ]);
        Responsible::create([
            'title' => 'Борисова Светлана Михайловна',
            'position' => 3
        ]);
        Responsible::create([
            'title' => 'Топталина Ольга Фаритовна',
            'position' => 3
        ]);
        Responsible::create([
            'title' => 'Демьянов Станислав Владимирович',
            'position' => 3
        ]);
        Responsible::create([
            'title' => 'Лизнев Павел Николаевич',
            'position' => 4
        ]);
        Responsible::create([
            'title' => 'Гимпу Эдуард Михайлович',
            'position' => 4
        ]);
        Responsible::create([
            'title' => 'Илашко Сергей',
            'position' => 5
        ]);
        Responsible::create([
            'title' => 'Колесников Вадим Николаевич',
            'position' => 5
        ]);
    }
}
