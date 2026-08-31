<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поля правил линий и продукции и заполняет их по данным старой версии.
     *
     * Обратная совместимость с прошлой версией приложения:
     * - столбцы только добавляются; существующие данные (line_id, title, type_id и т.д.) не меняются;
     * - старая версия новые столбцы игнорирует и продолжает работать как раньше,
     *   поэтому эту миграцию можно применять заранее, до деплоя новой версии.
     *
     * Переключение type_id = 3 для сборки ящиков вынесено в отдельную миграцию
     * 2026_08_31_000001_set_crate_lines_type3.php — её нужно применять
     * вместе с деплоем новой версии кода (старая версия требует type_id = 2 у линии 37).
     */
    public function up(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->unsignedTinyInteger('return_type')->nullable()->after('type_id');
            $table->boolean('use_dating')->default(false)->after('return_type');
        });

        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->boolean('televisor')->default(false);
        });

        // --- Заполняем данные так, чтобы новая версия вела себя как старая ---

        // Возвратные массы: паттерны названий из старого Util::calcReturnMass.
        // Порядок проверок совпадает со старым кодом, поэтому результат идентичен
        // старому поведению (в т.ч. «непрерывная линия» имеет приоритет над «шоколадной»).
        DB::table('lines')
            ->whereRaw('LOWER(title) LIKE ?', ['%непрерывная линия%'])
            ->update(['return_type' => 1]);
        DB::table('lines')
            ->whereNull('return_type')
            ->whereRaw('LOWER(title) LIKE ?', ['%шоколадная линия%'])
            ->update(['return_type' => 2]);
        DB::table('lines')
            ->whereNull('return_type')
            ->whereRaw('LOWER(title) LIKE ?', ['%полуавт%'])
            ->update(['return_type' => 3]);
        DB::table('lines')
            ->whereNull('return_type')
            ->whereRaw('LOWER(title) LIKE ?', ['%shot%'])
            ->update(['return_type' => 4]);

        // Датирование: старые захардкоженные списки эталонных линий:
        // зефир: [14, 17, 18, 20, 24, 25, 41, 51], конфеты: [31], суфле: [20]
        DB::table('lines')
            ->whereIn('line_id', [14, 17, 18, 20, 24, 25, 31, 41, 51])
            ->update(['use_dating' => true]);

        // Телевизоры: старая версия определяла их по названию продукции
        DB::table('products_dictionary')
            ->whereRaw('LOWER(title) LIKE ?', ['%телевизор%'])
            ->update(['televisor' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lines')->update(['return_type' => null, 'use_dating' => false]);
        DB::table('products_dictionary')->update(['televisor' => false]);

        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn(['return_type', 'use_dating']);
        });

        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->dropColumn('televisor');
        });
    }
};
