<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    static public function add($shift = null, $line_id = null, $title = null, $workers_count = null, $started_at = null, $ended_at = null)
    {
        if ($title == null)
            return;

        $product = new Products();

        $product->shift = $shift;
        $product->title = $title;
        $product->line_id = $line_id;
        $product->workers_count = $workers_count;
        $product->started_at = $started_at;
        $product->ended_at = $ended_at;

        $product->save();
        return;
    }

    static public function afterLineUpdate($line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    {
        $slots = Products::where('line_id', '=', $line_id)->where('started_at', '=', $oldStart)->get();
        foreach ($slots as $slot) {
            $slot->started_at = $newStart;
            $slot->save();
        }

        $slots = Products::where('line_id', '=', $line_id)->where('ended_At', '=', $oldEnd)->get();
        foreach ($slots as $slot) {
            $slot->ended_at = $newEnd;
            $slot->save();
        }
    }

    static public function clear()
    {
        return Products::truncate();
    }
}
