<?php

namespace Database\Seeders;

use App\Models\Hardware;
use App\Models\ReturnType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Справочники окна «Производство».
     * ID оборудования и режимов фиксированы: на них ссылаются
     * products_slots.hardware (1–6) и lines.return_type (1–4).
     */
    public function run(): void
    {
        $hardwares = [
            ['hardware_id' => 1, 'title' => 'ТОРНАДО', 'full_title' => 'Торнадо', 'type' => 1],
            ['hardware_id' => 2, 'title' => 'Мондомикс', 'full_title' => 'Мондомикс', 'type' => 1],
            ['hardware_id' => 3, 'title' => 'Китайский Аэрос', 'full_title' => 'Китайский Аэрос', 'type' => 1],
            ['hardware_id' => 4, 'title' => 'ЗМ №1', 'full_title' => 'Завёрточная машина №1', 'type' => 2],
            ['hardware_id' => 5, 'title' => 'ЗМ №2', 'full_title' => 'Завёрточная машина №2', 'type' => 2],
            ['hardware_id' => 6, 'title' => 'ЗМ №1 и №2', 'full_title' => 'Завёрточные машины №1 и №2', 'type' => 2],
        ];
        foreach ($hardwares as $row) {
            Hardware::create($row);
        }

        // coef_k = null: категория «конфеты» не участвует (как сейчас)
        $returnTypes = [
            ['return_type_id' => 1, 'title' => 'Непрерывная линия', 'formula_type' => 'fixed', 'fixed_value' => 25],
            ['return_type_id' => 2, 'title' => 'Шоколадная линия', 'formula_type' => 'coef', 'coef_z' => 0.015, 'coef_s' => 0.00405],
            ['return_type_id' => 3, 'title' => 'Линия-полуавтомат', 'formula_type' => 'coef', 'coef_z' => 0.025],
            ['return_type_id' => 4, 'title' => 'One-Shot', 'formula_type' => 'coef', 'coef_z' => 0.005],
        ];
        foreach ($returnTypes as $row) {
            ReturnType::create($row);
        }

        $settings = [
            ['key' => 'interval_boil', 'value' => '10'],
            ['key' => 'interval_pack', 'value' => '15'],
            ['key' => 'zm_perfomance', 'value' => '143.5'],
            ['key' => 'zm_perfomance2', 'value' => '287'],
        ];
        foreach ($settings as $row) {
            Setting::create($row);
        }
    }
}
