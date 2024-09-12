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
        Schema::table('products', function(Blueprint $table) {
            $table->integer('workers_count');
            $table->string('shift');
            $table->dateTime('updated_at')->default(now());
            $table->dateTime('created_at')->default(now());
            $table->dropColumn('started_at');
            $table->dropColumn('ended_at');
            $table->time('started_at');
            $table->time('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function(Blueprint $table) {
            $table->dropColumn('workers_count', 'updated_at', 'created_at', 'shift', 'started_at', 'ended_at');
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
        });
    }
};
