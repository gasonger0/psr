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
        Schema::create('slots', function (Blueprint $table) {
            $table->id('slot_id')->primary()->autoIncrement();
            $table->string('title');
            $table->integer('workers_id');
            $table->dateTime('created_at')->default(now());
            $table->dateTime('updated_at')->default(now());
            $table->time('started_at');
            $table->time('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
