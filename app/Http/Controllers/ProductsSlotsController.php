<?php

namespace App\Http\Controllers;

use App\Models\ProductsSlots;
use Illuminate\Http\Request;

class ProductsSlotsController extends Controller
{
    public function getList(Request $request)
    {
        $type_id = $request->post('type_id');
        $id = $request->post('product_id');
        if ($id) {
            return ProductsSlots::where('product_id', '=', $id)->get()->toJson();
        } else if ($type_id) {
            return ProductsSlots::where('type_id', '=', $type_id)->get()->toJson();
        } else {
            return ProductsSlots::all()->toJson();
        }
    }

    public function addSlots(Request $request)
    {
        $keys = ['hardware', 'people_count', 'perfomance'];
        $ret = [];
        foreach ($request->post() as $slot) {
            if ($slot['product_slot_id'] == -1) {
                $s = new ProductsSlots();
                $s->product_id          = $slot['product_id'];
                $s->line_id             = $slot['line_id'];
                $s->people_count        = $slot['people_count'] ?? 0;
                $s->perfomance          = $slot['perfomance'] ?? 0;
                $s->hardware            = $slot['hardware'] ?? null;
                // $s->order               = $slot['order'];
                $s->type_id             = $slot['type_id'] ?? 1;
                $s->save();
                $ret[] = $s->product_slot_id;
            } else {
                $oldSlot = ProductsSlots::find($slot['product_slot_id']);
                foreach ($keys as $k) {
                    if ($oldSlot->$k != $slot[$k]) {
                        $oldSlot->$k = $slot[$k];
                    }
                }
                $oldSlot->save();
                $ret[] = $oldSlot->product_slot_id;
            }
        }
        return json_encode($ret); 
    }

    public function delete(Request $request)
    {
        ProductsSlots::find($request->post('product_slot_id'))->delete();
    }

    static public function clear()
    {
        ProductsSlots::truncate();
    }
}
