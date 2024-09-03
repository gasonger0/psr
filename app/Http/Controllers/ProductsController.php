<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    static public function add($shift = null, $line_id = null, $title = null, $workers_count = null, $started_at = null, $ended_at = null) {
        if ($title == null) return;
     
        $product = new Products();

        // $product->shift = $shift;
        $product->title = $title;
        $product->line_id = $line_id;
        $product->workers_count = $workers_count;
        $product->started_at = $started_at;
        $product->ended_at = $ended_at;

        $product->save();

        return;
    }

    static public function dropData() {
        return Products::truncate();
    }
}
