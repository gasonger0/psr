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
        Schema::table('products_order', function(Blueprint $table) {
            $table->datetime('created_at')->default(now());
            $table->datetime('updated_at')->default(now());
        });

        Schema::table('products_plan', function(Blueprint $table) {
            $table->string('hardware')->nullable();
            $table->integer('colon')->nullable();
        });

        Schema::table('products_dictionary', function(Blueprint $table) {
            $table->string('cars2plates')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    }
};
