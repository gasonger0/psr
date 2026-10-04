<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Удаляет колонки, затенённые lines_extra / lines_defaults:
     * - lines.workers_count/started_at/ended_at перекрываются lines_extra
     *   при array_merge в LinesController::get и TableController (extra создаётся getOrInsert всегда);
     * - lines.prep_time/after_time — не в $fillable модели, никогда не пишутся, читаются из extra;
     * - lines_defaults.title не попадает в lines_extra (нет в $fillable),
     *   lines_defaults.started_at/ended_at всегда перезаписываются Util::createDate по графику роли.
     */
    public function up(): void
    {
        // Часть этих колонок уже отсутствует в схеме: их удалила миграция
        // 2025_02_13_092041_add_day_column (up). Поэтому здесь удаляем только те,
        // что реально остались, — иначе MySQL падает с ошибкой 1091.
        $lineColumns = array_values(array_filter(
            ['workers_count', 'started_at', 'ended_at', 'prep_time', 'after_time'],
            fn (string $column) => Schema::hasColumn('lines', $column)
        ));

        if ($lineColumns !== []) {
            Schema::table('lines', function (Blueprint $table) use ($lineColumns) {
                $table->dropColumn($lineColumns);
            });
        }

        Schema::table('lines_defaults', function (Blueprint $table) {
            $table->dropColumn(['title', 'started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->integer('workers_count')->nullable();
            $table->time('started_at')->nullable();
            $table->time('ended_at')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('after_time')->nullable();
        });

        Schema::table('lines_defaults', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('started_at')->nullable();
            $table->string('ended_at')->nullable();
        });
    }
};
