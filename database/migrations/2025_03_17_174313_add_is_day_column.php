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
            $table->boolean('isDay')->default(true); 
        });
        Schema::table('logs', function(Blueprint $table) {
            $table->boolean('isDay')->default(true); 
        });
        Schema::table('products_plan', function(Blueprint $table) {
            $table->boolean('isDay')->default(true); 
        });
        Schema::table('products_order', function(Blueprint $table) {
            $table->boolean('isDay')->default(true);
        });
        Schema::table('line_extra', function (Blueprint $table) {
            $table->boolean('isDay')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function(Blueprint $table) {
           $table->dropColumn('isDay'); 
        });
        Schema::table('logs', function(Blueprint $table) {
            $table->dropColumn('isDay'); 
        });
        Schema::table('products_plan', function(Blueprint $table) {
            $table->dropColumn('isDay'); 
        });
        Schema::table('products_order', function(Blueprint $table) {
            $table->dropColumn('isDay');
        });
        Schema::table('line_extra', function (Blueprint $table) {
            $table->dropColumn('isDay');
        });

    }
};
