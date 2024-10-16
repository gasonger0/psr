<?php

namespace App\Http\Controllers;

use App\Models\Products_dictionary;
use Illuminate\Http\Request;

class ProductsDictionaryController extends Controller
{
    public function getList(Request $request){
        if ($id = $request->post('category_id')) {
            return Products_dictionary::where('category_id', '=', $id)->get()->toJson();
        }
        return Products_dictionary::all()->toJson();
    }
}
