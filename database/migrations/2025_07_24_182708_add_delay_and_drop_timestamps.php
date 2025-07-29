<?php

use App\Models\LinesExtra;
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
        Schema::table("lines", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("products_dictionary", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("products_order", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("products_slots", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("responsible", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("slots", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table("workers", function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table("products_plan", function (Blueprint $table) {
            $table->dropColumn([
                'created_at',
                'updated_at',
                'workers_count',
                'hardware',
                'type_id',
                'position'
            ]);

            $table->unsignedSmallInteger('delay')->nullable();

            $table->unsignedBigInteger('parent')->nullable();
        });

        LinesExtra::truncate();
        Schema::table('lines_extra', function (Blueprint $table) {
            $table->datetime('started_at')->change();
            $table->datetime('ended_at')->change();

            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id('company_id')->primary()->autoIncrement();
            $table->string('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
