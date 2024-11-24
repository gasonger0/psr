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
            'name' => 'Иванова Алена Сергеева',
            'position' => 1
        ]);
        Responsible::create([
            'name' => 'Чинякина Светлана Викторовна',
            'position' => 1
        ]);
        Responsible::create([
            'name' => 'Жукова Марина Петровна',
            'position' => 2
        ]);
        Responsible::create([
            'name' => 'Степанова Людмила Вячеславовна',
            'position' => 2
        ]);
        Responsible::create([
            'name' => 'Васильева Светлана Валерьевна',
            'position' => 2
        ]);
        Responsible::create([
            'name' => 'Борисова Светлана Михайловна',
            'position' => 3
        ]);
        Responsible::create([
            'name' => 'Топталина Ольга Фаритовна',
            'position' => 3
        ]);
        Responsible::create([
            'name' => 'Демьянов Станислав Владимирович',
            'position' => 3
        ]);
        Responsible::create([
            'name' => 'Лизнев Павел Николаевич',
            'position' => 4
        ]);
        Responsible::create([
            'name' => 'Гимпу Эдуард Михайлович',
            'position' => 4
        ]);
        Responsible::create([
            'name' => 'Илашко Сергей',
            'position' => 5
        ]);
        Responsible::create([
            'name' => 'Колесников Вадим Николаевич',
            'position' => 5
        ]);
    }
}
