<?php

use App\Models\ProductsCategories;
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
        Schema::table('products_categories', function (Blueprint $table) {
            $table->tinyInteger('pack')->default(1);
        });
        // Фасовка
        ProductsCategories::whereIn('category_id', [4])->update(['pack' => 1]);
        // Весовка
        ProductsCategories::whereIn('category_id', [3, 5, 17])->update(['pack' => 2]);
        // Смешанное
        ProductsCategories::whereIn('category_id', [25, 26, 27, 28, 29])->update(['pack' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_categories', function (Blueprint $table) {
            $table->dropColumn('pack');
        });
    }
};
