<?php

use App\Models\ProductsCategories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("products_plan", function (Blueprint $table) {
            $table->dropColumn("zm_id");
            $table->unsignedSmallInteger("hardware")->nullable();
        });
        $attrs = [
            [
                'title' => "1.7. Зефир без сахара",
                'type_id' => 1,
                'parent' => 1
            ],
            [
                'title' => "1.2.9. Муми Милк (США)",
                'type_id' => 1,
                'parent' => 4
            ],
            [
                'title' => "1.1.5. Ветлайф (Грузия)",
                'type_id' => 2,
                'parent' => 3
            ]
        ];
        foreach ($attrs as $cat) {
            ProductsCategories::create($cat);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("products_plan", function (Blueprint $table) {
            $table->dropColumn("hardware");
            $table->unsignedSmallInteger("zm_id")->nullable();
        });
    }
};
