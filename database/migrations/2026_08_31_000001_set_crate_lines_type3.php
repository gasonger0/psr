<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Переключает сборку ящиков на type_id = 3.
     *
     * ВНИМАНИЕ: эта миграция разрывает совместимость с прошлой версией приложения —
     * старая версия считает линию 37 упаковкой (type_id = 2) и не понимает type_id = 3
     * (распределение листов отчёта, время работы по умолчанию, подстраховка заказа).
     *
     * Применяйте эту миграцию ТОЛЬКО вместе с деплоем новой версии кода.
     * Для отката: migrate:rollback вернёт type_id = 2, после чего старую версию
     * можно запускать снова.
     */
    public function up(): void
    {
        DB::table('lines')
            ->where('line_id', 37)
            ->update(['type_id' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lines')
            ->where('line_id', 37)
            ->where('type_id', 3)
            ->update(['type_id' => 2]);
    }
};
