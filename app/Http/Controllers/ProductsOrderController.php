<?php

namespace App\Http\Controllers;

use App\Models\ProductsOrder;
use Illuminate\Http\Request;

class ProductsOrderController extends Controller
{
    public function getList(Request $request){
        $date = $request->cookie('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        return ProductsOrder::where('date', $date)->where('isDay', $isDay)->get();
    }
}
