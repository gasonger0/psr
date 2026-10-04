<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Справочники и настройки окна «Производство».
     * hardware_id 1–6 и return_type_id 1–4 задаются сидом явно,
     * чтобы ссылки из products_slots.hardware и lines.return_type не ломались.
     */
    public function up(): void
    {
        Schema::create('hardwares', function (Blueprint $table) {
            $table->id('hardware_id');
            $table->string('title');
            $table->string('full_title')->nullable();
            $table->unsignedTinyInteger('type'); // 1 — варка, 2 — упаковка
        });

        Schema::create('return_types', function (Blueprint $table) {
            $table->id('return_type_id');
            $table->string('title');
            $table->enum('formula_type', ['fixed', 'coef']);
            $table->decimal('fixed_value', 10, 6)->nullable();
            $table->decimal('coef_z', 10, 6)->nullable();
            $table->decimal('coef_s', 10, 6)->nullable();
            $table->decimal('coef_k', 10, 6)->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('return_types');
        Schema::dropIfExists('hardwares');
    }
};
