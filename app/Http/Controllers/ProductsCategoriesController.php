<?php

namespace App\Http\Controllers;

use App\Models\ProductsCategories;
use App\Util;
use Illuminate\Http\Request;


class ProductsCategoriesController extends Controller
{
    public const PACK = 1;
    public const BY_WEIGHT = 2;
    public const MULTI = 3;

    private const CATEGORY_ALREADY_EXISTS = 'Такая категория уже существует';
    private const CATEGORY_NOT_EXISTS = 'Такой категории не существует.';

    public function get() {
        return ProductsCategories::all()->toArray();
    }

    public static function getByName(string $name){
        return ProductsCategories::whereRaw('UPPER(title) = ?', [$name])->first();
    }

    public function create(Request $request)
    {
        $exists = Util::checkDublicate(new ProductsCategories(), ['title'], $request->post());
        if ($exists) {
            return Util::errorMsg(self::CATEGORY_ALREADY_EXISTS, 400);
        }
        $result = ProductsCategories::create(
            $request->only(
                (new ProductsCategories())->getFillable()
            )
        );

        if ($result) {
            return Util::successMsg($result->toArray(), 201);
        } else {
            return Util::errorMsg($result, 400);
        }
    }

    public function update(Request $request)
    {
        $category = ProductsCategories::find($request->post('category_id'));
        if (!$category) {
            return Util::errorMsg(self::CATEGORY_NOT_EXISTS, 404);
        }

        $result = $category->update(
            $request->only(
                (new ProductsCategories())->getFillable()
            )
        );

        if ($result) {
            return Util::successMsg('Категория изменена', 200);
        } else {
            return Util::errorMsg($result, 400);
        }
    }

    public function delete(Request $request)
    {
        $delete = ProductsCategories::find($request->post('category_id'))->delete();

        if ($delete) {
            return Util::successMsg('Категория удалена', 200);
        } else {
            return Util::errorMsg($delete, 400);
        }
    }
}
