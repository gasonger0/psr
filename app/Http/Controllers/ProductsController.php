<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    static public function insertFromXlsx($data = []) {
        if ($data == []) {
            return;
        }
        $result = [];
        foreach ($data as $prod) {
            $id = Products::insertGetId([
                'title' => $prod]);
            $result[$prod] = $id;
        }

        return $result;
    }

    static public function prepareProducts($data) {
        $unique = array_unique($data);
        if (count($unique) > 0) {
            return self::insertFromXlsx($unique);
        }
    } 
}
