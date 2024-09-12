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
        Schema::table('slots', function (Blueprint $table) {
            // $table->dropColumn('down_time');
            $table->time('down_time')->nullable()->default('00:00:00');
        });
        Schema::table('lines', function (Blueprint $table) {
            // $table->dropColumn('down_time');
            // $table->dropColumn('down_from');
            $table->time('down_time')->nullable()->default('00:00:00');
            $table->time('down_from')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('down_time');
        });
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn('down_time');
            $table->dropColumn('down_from');
        });

    }
};
