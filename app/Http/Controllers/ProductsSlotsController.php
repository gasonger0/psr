<?php

namespace App\Http\Controllers;

use App\Models\ProductsSlots;
use Illuminate\Http\Request;

class ProductsSlotsController extends Controller
{
    public function getList(Request $request) {
        if (!($id = $request->post('product_id'))) {
            return [];
        } else {
            return ProductsSlots::where('product_id', '=', $id)->orderBy('order', 'DESC')->get()->toJson();
        }
    }
}
