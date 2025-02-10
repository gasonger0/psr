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
        Schema::table('lines', function (Blueprint $table) {
            $table->boolean('has_detector')->default(false);
            $table->time('detector_start')->nullable();
            $table->time('detector_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn('has_detector');
            $table->dropColumn('detector_start');
            $table->dropColumn('detector_end');
        });
    }
};
