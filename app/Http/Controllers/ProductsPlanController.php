<?php

namespace App\Http\Controllers;

use App\Models\ProductsPlan;
use Illuminate\Http\Request;

class ProductsPlanController extends Controller
{
    public function getList(Request $request) {
        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::all()->toJson();
        } else {
            return ProductsPlan::where('product_id', '=', $id)->get()->toJson();
        }
    }
}
