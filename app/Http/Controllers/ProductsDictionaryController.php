<?php

namespace App\Http\Controllers;

use App\Models\ProductsDictionary;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductsDictionaryController extends Controller
{
    public function getList(Request $request)
    {
        if ($id = $request->post('category_id')) {
            return ProductsDictionary::where('category_id', '=', $id)->get()->toJson();
        } else if (($pack = $request->post('packaged')) !== null) {
            $pack =
                $pack ? [4, 25, 26, 27, 28, 29] : [3, 5, 17, 25, 26, 27, 28, 29];

            $categories = ProductsCategoriesController::getList();
            $children = array_filter($categories, function ($el) use ($pack) {
                return (array_search($el['parent'], $pack) !== false || array_search($el['category_id'], $pack) !== false);
            });
            $children = array_map(function ($el) {
                return $el['category_id'];
            }, $children);
            $show = ProductsDictionary::whereIn('category_id', $children)->get()->toArray();
            $hide = ProductsDictionary::whereNotIn('category_id', $children)->get()->toArray();
            $ret = array_merge(
                array_map(function ($el) { 
                    $el['hide'] = false; 
                    return $el; 
                }, $show), 
                array_map(function ($el) { 
                    $el['hide'] = true; 
                    return $el; 
                }, $hide));
            return json_encode($ret);
        } else {
            return ProductsDictionary::all()->toJson();
        }
    }

    public function saveProduct(Request $request)
    {
        $prod = $request->post();
        if ($prod['title'] == null) {
            return -1;
        }
        if ($prod['product_id'] == -1) {
            $p = new ProductsDictionary();
            $p->title = $prod['title'];
            $p->category_id = $prod['category_id'];
            $p->save();
        } else {
            $oldProd = ProductsDictionary::find($prod['product_id']);
            $updateKeys = array_filter($prod, function ($v, $k) use ($oldProd) {
                return $v != $oldProd->toArray()[$k];
            }, ARRAY_FILTER_USE_BOTH);
            var_dump($oldProd->toArray());
            var_dump($prod);
            if ($updateKeys) {
                foreach ($updateKeys as $k => $v) {
                    $oldProd->$k = $v;
                }
                $oldProd->save();
            }
        }
    }

    public function deleteProduct(Request $request)
    {
        if (!$request->post('product_id')) die(new Response('Нет ID продукта', 400));
        $prod = ProductsDictionary::find($request->post('product_id'));
        if ($prod) {
            $prod->delete();
        }
        return 0;
    }

    static public function clear()
    {
        ProductsDictionary::truncate();
        return 1;
    }
}
