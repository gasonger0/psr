<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поле «Повтор» (can_repeat) у линий: разрешает показывать линию при повторной
     * постановке продукции (drag'n'drop), когда варка уже стоит в плане.
     * Заменяет захардкоженный список alwaysShowLines = [8,9,10,11,12,50] на фронте.
     *
     * Обратная совместимость: столбец только добавляется, данные заполняются так,
     * чтобы новая версия вела себя как старая (те же линии с can_repeat = true).
     */
    public function up(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->boolean('can_repeat')->default(false)->after('use_dating');
        });

        // Старые захардкоженные линии: сохраняем прежнее поведение борда
        DB::table('lines')
            ->whereIn('line_id', [8, 9, 10, 11, 12, 50])
            ->update(['can_repeat' => true]);
    }

    public function down(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn('can_repeat');
        });
    }
};
