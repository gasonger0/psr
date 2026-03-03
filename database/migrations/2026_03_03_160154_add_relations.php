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
        Schema::table('products_plan', function(Blueprint $table) {
            $table
                ->foreign('slot_id')
                ->references('product_slot_id')
                ->on('products_slots')
                ->cascadeOnDelete();
        });
        // Schema::table('products_slots', function(Blueprint $table) {
        //     $table
        //         ->foreign('product_id')
        //         ->references('product_id')
        //         ->on('products_dictionary')
        //         ->cascadeOnDelete();
        //     $table
        //         ->foreign('line_id')
        //         ->references('line_id')
        //         ->on('lines')
        //         ->cascadeOnDelete();
        // });
        // Schema::table('logs', )
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
