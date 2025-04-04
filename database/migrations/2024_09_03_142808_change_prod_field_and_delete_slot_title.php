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
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->renameColumn('workers_id', 'worker_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('people_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->string('title');
            $table->renameColumn('worker_id', 'workers_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->integer('people_count');
        });
    }
};
