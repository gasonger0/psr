<?php

namespace App\Http\Controllers;

use App\Models\ProductsCategories;


class ProductsCategoriesController extends Controller
{
    public const PACK = 1;
    public const BY_WEIGHT = 2;
    public const MULTI = 3;

    public function get() {
        return ProductsCategories::all()->toArray();
    }

    public static function getByName(string $name){
        return ProductsCategories::whereRaw('UPPER(title) = ?', [$name])->first();
    }
}
