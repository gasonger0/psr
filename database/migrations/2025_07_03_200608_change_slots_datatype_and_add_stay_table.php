<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('line_extra', 'lines_extra');
        // Задаём отношения для:
        // 1) Линий и смен
        Schema::table('line_extra', function (Blueprint $table) {
            $table->unsignedBigInteger('line_id')->change();
            $table->foreign('line_id')->references('line_id')->on('lines')->onDelete('cascade');
        });
        // 2) Слотов сотрудников и сотрудников
        // 3) Слотов сотрудников и линий
        Schema::table('slots', function (Blueprint $table) {
            $table->unsignedBigInteger('worker_id')->change();
            $table->foreign('worker_id')->references('worker_id')->on('workers')->onDelete('cascade');
            $table->unsignedBigInteger('line_id')->change();
            $table->foreign('line_id')->references('line_id')->on('lines')->onDelete('cascade');
            $table->dateTime('started_at')->change();
            $table->dateTime('ended_at')->change();
        });

        // Надо удалить неактуальные слоты
        DB::raw('delete from products_slots ps where ps.line_id NOT IN(select line_id from `lines`)');
        DB::raw('delete from products_slots ps where ps.product_id NOT IN(select product_id from `products_dictionary`)');
        // 4) Слотов ГП и продукции
        // 5) Слотов ГП и линий
        Schema::table('products_slots', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->unsignedBigInteger('line_id')->change();
            $table->foreign('product_id')->references('product_id')->on('products_dictionary')->onDelete('cascade');
            $table->foreign('line_id')->references('line_id')->on('lines')->onDelete('cascade');
        });
        // 6) Слотов изготовления ГП и слотов ГП
        Schema::table('products_plan', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->unsignedBigInteger('line_id')->change();
            $table->unsignedBigInteger('slot_id')->change();
            $table->foreign('product_id')->references('product_id')->on('products_dictionary')->onDelete('cascade');
            $table->foreign('line_id')->references('line_id')->on('lines')->onDelete('cascade');
            $table->foreign('slot_id')->references('product_slot_id')->on('products_slots')->onDelete('cascade');
            $table->dateTime('started_at')->change();
            $table->dateTime('ended_at')->change();
        });

        Schema::table('products_order', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->foreign('product_id')->references('product_id')->on('products_dictionary')->onDelete('cascade');
        });

        // Schema::create('lines_stops', function (Blueprint $table) {
        //     $table->id('stop_id')->primary()->autoIncrement();
        // TODO подумать надо логами
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
