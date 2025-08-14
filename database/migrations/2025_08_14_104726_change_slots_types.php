<?php

use App\Models\ProductsSlots;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обсыпка: 
        //     18 Непрерывная линия сахарной пудры №1, 
        //     25 Полуавтоматическая линия сахарной пудры, 
        //     41 Непрерывная линия №2 - сахарная пудра. 
        ProductsSlots::whereIn('line_id', [18, 25, 41])->each(function ($slot) {
            $slot->update([
                'type_id' => 4
            ]);
        });
        // Глазировка: 
        //     20 Шоколадная линия 1, 
        //     17 Непрерывная линия сахарной пудры №1 - Шоколадная линия, 
        //     14 НЕПРЕРЫВНАЯ ЛИНИЯ №2 - Шоколадная линия 2, 
        //     24 Линия эквивалент
        ProductsSlots::whereIn('line_id', [20, 17, 14, 24])->each(function ($slot) {
            $slot->update([
                'type_id' => 3
            ]);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ProductsSlots::whereIn('type_id', [3, 4])->each(function ($slot) {
            $slot->update(['type_id' => 2]);
        });
    }
};
