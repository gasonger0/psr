<?php

namespace App\Http\Controllers;

use App\Models\Products_dictionary;
use Illuminate\Http\Request;

class ProductsDictionaryController extends Controller
{
    public function getList(){
        return Products_dictionary::all()->toJson();
    }
}
