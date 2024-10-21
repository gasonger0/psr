<?php

namespace App\Http\Controllers;

use App\Models\ProductsSlots;
use Illuminate\Http\Request;

class ProductsSlotsController extends Controller
{
    public function getList(Request $request) {
        if (!($id = $request->post('product_id'))) {
            return ProductsSlots::orderBy('order', 'ASC')->get()->toJson();
        } else {
            return ProductsSlots::where('product_id', '=', $id)->orderBy('order', 'DESC')->get()->toJson();
        }
    }

    public function addSlots(Request $request){
        foreach($request->post() as $slot) {
            if ($slot['product_slot_id'] == -1){
                $s = new ProductsSlots();
                $s->product_id          = $slot['product_id'];
                $s->line_id             = $slot['line_id'];
                $s->people_count        = $slot['people_count'];
                $s->perfomance          = $slot['perfomance'];
                $s->order               = $slot['order'];
                $s->save();
            } else {
                $oldSlot = ProductsSlots::find($slot['product_slot_id']);
                $updateKeys = array_diff($slot, $oldSlot->toArray());
                if ($updateKeys) {
                    foreach ($updateKeys as $k => $v) {
                        $oldSlot->$k = $v;
                    }
                    $oldSlot->save();
                }
            }
        }
    }
}
