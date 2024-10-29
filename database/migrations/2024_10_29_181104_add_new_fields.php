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
        Schema::table('lines', function(Blueprint $table) {
            $table->string('type_id');
        });

        Schema::table('products_plan', function(Blueprint $table) {
            $table->integer('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lines', function(Blueprint $table) {
            $table->dropColumn('type_id');
        });

        Schema::table('products_plan', function(Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
