<?php

namespace App\Http\Controllers;

use App\Models\ProductsDictionary;
use Illuminate\Http\Request;

class ProductsDictionaryController extends Controller
{
    public function getList(Request $request)
    {
        if ($id = $request->post('category_id')) {
            return ProductsDictionary::where('category_id', '=', $id)->get()->toJson();
        } else if (($pack = $request->post('packaged')) !== null) {
            $pack = 
                $pack ? [4] : [3,5];

            $categories = ProductsCategoriesController::getList();
            $children = array_filter($categories, function($el) use ($pack) {
                return (array_search($el['parent'], $pack) !== false);
            });
            $children = array_map(function($el) {
                return $el['category_id'];
            }, $children);
            return ProductsDictionary::whereIn('category_id', $children)->get()->toJson();
        } else {
            return ProductsDictionary::all()->toJson();
        }
    }

    public function addProduct(Request $request)
    {
        foreach ($request->post() as $prod) {
            if ($prod['title'] == null) {
                continue;
            }
            if ($prod['product_id'] == -1) {
                $p = new ProductsDictionary();
                $p->title = $prod['title'];
                $p->category_id = $prod['category_id'];
                $p->save();
            } else {
                $oldProd = ProductsDictionary::find($prod['product_id']);
                $updateKeys = array_diff($prod, $oldProd->toArray());
                var_dump($updateKeys);
                if ($updateKeys) {
                    foreach ($updateKeys as $k => $v) {
                        $oldProd->$k = $v;
                    }
                    $oldProd->save();
                }
            }
        }
    }
}
