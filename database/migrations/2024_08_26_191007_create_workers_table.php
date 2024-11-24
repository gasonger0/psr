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
        Schema::create('workers', function (Blueprint $table) {
            $table->id('worker_id')->primary()->autoIncrement();
            $table->string('title');
            $table->dateTime('created_at')->default(now());
            $table->dateTime('updated_at')->default(now());
            $table->time('break_started_at')->nullable();
            $table->time('break_ended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
