<?php

use App\Models\ProductsDictionary;
use App\Models\ProductsSlots;
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
        ProductsSlots::where('line_id', 31)->each(function($i) {
            $i->delete();
        });

        ProductsDictionary::whereIn('category_id', [14, 16, 18, 19, 20])->each(function($prod) {
            $data = [
                'product_id' => $prod->product_id,
                'line_id' => 31,
                'people_count' => 2,
                'type_id' => 2,
            ];

            ProductsSlots::insert($data + [
                'perfomance' => 143.5,
                'hardware' => 4
            ]);
            ProductsSlots::insert($data + [
                'perfomance' => 143.5,
                'hardware' => 5
            ]);
            ProductsSlots::insert($data + [
                'perfomance' => 287,
                'hardware' => 6
            ]);
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
