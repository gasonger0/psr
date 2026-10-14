<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поля «Расчёт (варка)» / «Расчёт (упаковка)» у линий + справочник подтипов зефира
     * + подтип зефира у продукции. Заменяет захардкоженные списки в TableController.
     *
     * Обратная совместимость с прошлой версией:
     * - столбцы/таблица только добавляются; существующие данные не меняются;
     * - старая версия новые столбцы игнорирует, поэтому миграцию можно применять заранее.
     * - данные заполняются так, чтобы новая версия вела себя как старая (те же итоги).
     */
    public function up(): void
    {
        // 1) Справочник подтипов зефира (порядок id = порядок строк «ИТОГО»)
        Schema::create('zephyr_types', function (Blueprint $table) {
            $table->id('zephyr_type_id');
            $table->string('title');
            $table->boolean('is_default')->default(false);
        });

        // 2) Линии: расчёт варки + расчёт упаковки
        Schema::table('lines', function (Blueprint $table) {
            $table->boolean('calc_boil')->default(false)->after('use_dating');
            $table->json('pack_categories')->nullable()->after('calc_boil');
        });

        // 3) Продукция: подтип зефира. Ссылка логическая (как hardwares/return_types):
        //    целостность держит delete-guard в ProductionController, а не DB-констрейнт.
        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->unsignedInteger('zephyr_type_id')->nullable()->after('televisor');
        });

        // --- Заполняем данные так, чтобы новая версия вела себя как старая ---

        // Подтипы зефира: порядок id совпадает со старым порядком строк «ИТОГО».
        foreach ([
            ['zephyr_type_id' => 1, 'title' => 'агара', 'is_default' => false],
            ['zephyr_type_id' => 2, 'title' => 'желатина', 'is_default' => false],
            ['zephyr_type_id' => 3, 'title' => 'без сахара', 'is_default' => false],
            ['zephyr_type_id' => 4, 'title' => 'фруктозы', 'is_default' => false],
            ['zephyr_type_id' => 5, 'title' => 'на пектине', 'is_default' => true],
            ['zephyr_type_id' => 6, 'title' => 'крем-десерты', 'is_default' => false],
        ] as $row) {
            DB::table('zephyr_types')
                ->updateOrInsert(['zephyr_type_id' => $row['zephyr_type_id']], $row);
        }

        // Варка: старые захардкоженные линии [8..12]
        DB::table('lines')
            ->whereIn('line_id', [8, 9, 10, 11, 12])
            ->update(['calc_boil' => true]);

        // Упаковка: старые захардкоженные категории по линиям
        foreach ([
            14 => ['z'],
            17 => ['z'],
            18 => ['z'],
            20 => ['z', 's'],
            24 => ['z'],
            25 => ['z'],
            41 => ['z'],
            31 => ['k'],
        ] as $lineId => $cats) {
            DB::table('lines')
                ->where('line_id', $lineId)
                ->update(['pack_categories' => json_encode($cats)]);
        }

        // Подтип зефира у продукции: старые правила по названию (first-match-wins).
        // Порядок проверок и default совпадают со старым кодом, поэтому итоги идентичны.
        $groups = [];
        foreach (DB::table('products_dictionary')->get() as $p) {
            $titleLower = mb_strtolower($p->title);
            $key = 5; // по умолчанию «на пектине»
            foreach ([
                'агар' => 1,
                'желатин' => 2,
                '/без\s+(добавления\s+)?сахар[а]?/u' => 3,
                'фруктоз' => 4,
                'крем-десерт' => 6,
            ] as $pattern => $k) {
                if ($pattern[0] === '/'
                    ? preg_match($pattern, $titleLower)
                    : str_contains($titleLower, $pattern)
                ) {
                    $key = $k;
                    break;
                }
            }
            $groups[$key][] = $p->product_id;
        }
        foreach ($groups as $key => $ids) {
            DB::table('products_dictionary')
                ->whereIn('product_id', $ids)
                ->update(['zephyr_type_id' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products_dictionary')->update(['zephyr_type_id' => null]);
        DB::table('lines')->update(['calc_boil' => false, 'pack_categories' => null]);

        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->dropColumn('zephyr_type_id');
        });
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn(['calc_boil', 'pack_categories']);
        });

        Schema::dropIfExists('zephyr_types');
    }
};
