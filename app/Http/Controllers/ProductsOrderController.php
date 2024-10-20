<?php

namespace App\Http\Controllers;

use App\Models\ProductsOrder;
use Illuminate\Http\Request;

class ProductsOrderController extends Controller
{
    public function getList(){
        return ProductsOrder::all()->toJson();
    }
}
