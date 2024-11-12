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
        // Schema::table('products_plan', function(Blueprint $table) {
        //     $table->integer('position');
        // });
        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->string('amount2parts')->nullable();
            $table->string('parts2kg')->nullable();
            $table->string('kg2boil')->nullable();
            $table->string('cars')->nullable();
        });

        Schema::table('logs', function(Blueprint $table) {
            $table->string('people_count')->nullable();
        });

        Schema::create('responsible', function(Blueprint $table){
            $table->id('responsible_id')->primary()->autoIncrement();
            $table->string('name');
            $table->integer('position');
            $table->dateTime('created_at')->default(now());
            $table->dateTime('updated_at')->default(now());
        });
    
        Schema::table('lines', function(Blueprint $table) {
            $table->integer('master')->nullable();
            $table->integer('engineer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('products_plan', function(Blueprint $table) {
        //     $table->dropColumn('position');
        // });
        Schema::table('products_dictionary', function (Blueprint $table) {
            $table->dropColumn('amount2parts');
            $table->dropColumn('parts2kg');
            $table->dropColumn('kg2boil');
            $table->dropColumn('cars');
        });

        Schema::table('logs', function(Blueprint $table) {
            $table->dropColumn('people_count');
        });

        Schema::dropIfExists('responsible');

        Schema::table('lines', function(Blueprint $table) {
            $table->dropColumn('engineer');
            $table->dropColumn('master');
        });
    }
};
