<?php

namespace App\Http\Controllers;

use App\Models\ProductsDictionary;
use Illuminate\Http\Request;

class ProductsDictionaryController extends Controller
{
    public function getList(Request $request){
        if ($id = $request->post('category_id')) {
            return ProductsDictionary::where('category_id', '=', $id)->get()->toJson();
        }
        return ProductsDictionary::all()->toJson();
    }

    public function addProduct(Request $request) {
        foreach($request->post() as $prod) {
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
