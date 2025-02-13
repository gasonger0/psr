<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slots', function(Blueprint $table) {
            $table->date('date')->nullable();
        });
        Schema::table('products_plan', function(Blueprint $table) {
            $table->date('date')->nullable();
        });
        Schema::create('line_extra', function(Blueprint $table) {
            $table->id('line_extra_id')->primary()->autoIncrement();
            $table->integer('line_id');
            $table->dateTime('created_at')->default(now());
            $table->dateTime('updated_at')->default(now());
            $table->integer('workers_count')->nullable();
            $table->time('started_at');
            $table->time('ended_at');
            $table->time('down_from')->nullable();
            $table->integer('down_time')->nullable();
            $table->integer('cancel_reason')->nullable();
            $table->integer('master')->nullable();
            $table->integer('engineer')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('after_time')->nullable();
            $table->tinyinteger('has_detector')->nullable();
            $table->time('detector_start')->nullable();
            $table->time('detector_end')->nullable();
            $table->date('date')->nullable();
            $table->text('extra_title')->nullable();

        });
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn('workers_count');
            $table->dropColumn('started_at');
            $table->dropColumn('ended_at');
            $table->dropColumn('down_time');
            $table->dropColumn('down_from');
            $table->dropColumn('cancel_reason');
            $table->dropColumn('master');
            $table->dropColumn('engineer');
            $table->dropColumn('prep_time');
            $table->dropColumn('after_time');
            $table->dropColumn('has_detector');
            $table->dropColumn('detector_start');
            $table->dropColumn('detector_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function(Blueprint $table) {
            $table->dropColumn('date');
        });
        Schema::table('products_plan', function(Blueprint $table) {
            $table->dropColumn('date');
        });
        Schema::dropIfExists('line_extra');
        Schema::table('lines', function (Blueprint $table) {
            $table->integer('workers_count');
            $table->time('started_at');
            $table->time('ended_at');
            $table->time('down_from')->nullable();
            $table->integer('down_time')->nullable();
            $table->integer('cancel_reason')->nullable();
            $table->integer('master')->nullable();
            $table->integer('engineer')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('after_time')->nullable();
            $table->tinyinteger('has_detector')->nullable();
            $table->time('detector_start')->nullable();
            $table->time('detector_end')->nullable();
        });
    }
};
