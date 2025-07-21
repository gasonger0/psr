<?php

namespace App\Http\Controllers;

use App\Models\ProductsCategories;
use App\Models\ProductsDictionary;
use App\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductsDictionaryController extends Controller
{
    private const PRODUCT_ALREADY_EXISTS = 'Такая продукция уже существует';
    private const PRODUCT_NOT_EXISTS = 'Такой продукции не существует.';

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
                }, $hide)
            );
            return json_encode($ret);
        } else {
            return ProductsDictionary::all()->toJson();
        }
    }

    public function get(Request $request)
    {
        // $pack = $request->post('pack');
        // if ($pack === null) {
        // }
        $category_id = $request->post('category_id');
        if ($category_id !== null) { 
            return ProductsDictionary::where('category_id', $category_id)->get()->toArray();
        }

        return ProductsDictionary::all()->toArray();
        // $categories = ProductsCategories::all
        // return ProductsCategories::where('pack', $pack)->products()->each(function(&$prod) {
        //     $prod->hide = true;
        // })->toArray();
    }

    public function create(Request $request)
    {
        $exists = Util::checkDublicate(new ProductsDictionary, ['title'], $request->post());
        if ($exists) {
            return Util::errorMsg(self::PRODUCT_ALREADY_EXISTS, 400);
        }
        $request->mergeIfMissing(['category_id' => $request->post('category')['category_id']]);

        $result = ProductsDictionary::create(
            $request->only(
                (new ProductsDictionary())->getFillable()
            )
        );

        if ($result) {
            return Util::successMsg($result, 201);
        } else {
            return Util::errorMsg($result, 400);
        }
    }

    public function update(Request $request)
    {
        $product = ProductsDictionary::find($request->post('product_id'));
        if (!$product) {
            return Util::errorMsg(self::PRODUCT_NOT_EXISTS, 404);
        }

        $result = $product->update(
            $request->only(
                (new ProductsDictionary())->getFillable()
            )
        );

        if ($result) {
            return Util::successMsg('Продукция изменена', 200);
        } else {
            return Util::errorMsg($result, 400);
        }
    }

    public function delete(Request $request)
    {
        $delete = ProductsDictionary::find($request->post('product_id'))->delete();

        if ($delete) {
            return Util::successMsg('Продукция удалена', 200);
        } else {
            return Util::errorMsg($delete, 400);
        }
    }

    public function deleteProduct(Request $request)
    {
        if (!$request->post('product_id'))
            die(new Response('Нет ID продукта', 400));
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
