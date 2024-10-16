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
        Schema::create('products_dictionary', function(Blueprint $table) {
            $table->id('product_id')->primary()->autoIncrement();
            $table->string('title');
        });
        Schema::create('products_slots', function(Blueprint $table) {
            $table->id('product_slot_id')->primary()->autoIncrement();
            $table->integer('product_id');
            $table->integer('line_id');
            $table->integer('people_count');
            $table->integer('duration');
            $table->float('perfomance');
            $table->integer('order');
        });
        Schema::create('products_plan', function(Blueprint $table) {
            $table->id('plan_product_id')->primary()->autoIncrement();
            $table->integer('product_id');
            $table->integer('line_id');
            $table->time('started_at');
            $table->time('ended_at');
            $table->dateTime('created_at')->default(now());
            $table->dateTime('updated_at')->default(now());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('products_dictionary');
        Schema::drop('products_slots');
        Schema::drop('products_plan');
    }
};
