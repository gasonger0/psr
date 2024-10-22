<?php

namespace App\Http\Controllers;

use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use Illuminate\Http\Request;

class ProductsPlanController extends Controller
{
    public function getList(Request $request)
    {
        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::all()->toJson();
        } else {
            return ProductsPlan::where('product_id', '=', $id)->get()->toJson();
        }
    }

    public function addPlan(Request $request)
    {
        if ($post = $request->post()) {
            $old = ProductsPlan::where('slot_id', '=', $post['slot_id'])->get();
            if (!$old) {
                $plan = new ProductsPlan();
                $slot = ProductsSlots::find($post['slot_id'])->toArray();
                $plan->product_id = $slot['product_id'];
                $plan->line_id = $slot['line_id'];
                $plan->slot_id = $slot['product_slot_id'];
                $plan->started_at = $post['started_at'];
                $plan->ended_at = $post['ended_at'];
                $plan->save();
            }
        }
    }
}
