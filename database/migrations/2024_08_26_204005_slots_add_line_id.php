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
            $table->bigInteger('line_id');
            $table->bigInteger('workers_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function(Blueprint $table) {
            $table->dropColumn('line_id');
            $table->integer('workers_id')->change();
        });
    }
};
