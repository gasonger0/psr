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
        Schema::table('products_dictionary', function(Blueprint $table) {
            $table->integer('category_id');
        });

        Schema::create('products_categories', function(Blueprint $table) {
            $table->id('category_id')->primary()->autoIncrement();
            $table->string('title');
            $table->integer('parent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_dictionary', function(Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::dropTable('products_categories');
    }
};
