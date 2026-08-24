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
        Schema::create('lines_defaults', function (Blueprint $table) {
            $table->id('lines_default_id');
            $table->unsignedBigInteger('line_id')->unique();
            $table->string('title')->nullable();
            $table->string('perfomance')->nullable();
            $table->string('started_at')->nullable();
            $table->string('ended_at')->nullable();
            $table->integer('workers_count')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('after_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lines_defaults');
    }
};