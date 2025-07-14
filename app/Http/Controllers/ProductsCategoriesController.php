<?php

namespace App\Http\Controllers;

use App\Models\ProductsCategories;


class ProductsCategoriesController extends Controller
{
    public function getTree() {
        $data = ProductsCategories::all()->toArray();
        $tree = [];
        foreach (array_filter($data, 
            function($el) {
                return $el['parent'] == null;
            }) as $branch) {
                $tree[] = [
                    'category_id'   => $branch['category_id'],
                    'key'           => $branch['category_id'],
                    'title'         => $branch['title'],
                    'children'      => $this->fillTree($data, 
                        array_filter($data, function($br) use ($branch) {
                            return $br['parent'] == $branch['category_id'];
                        }))
                ]; 
        }
        return json_encode($tree);
    }

    static public function getList() {
        return ProductsCategories::all()->toArray();
    }

    private function fillTree($data = [], $branches = []) {
        $tree = [];
        foreach ($branches as $branch) {
            $tree[] = [
                'category_id'   => $branch['category_id'],
                'key'           => $branch['category_id'],
                'title'         => $branch['title'],
                'children'      => $this->fillTree($data, 
                array_filter($data, function($br) use ($branch) {
                    return $br['parent'] == $branch['category_id'];
                }))
            ];
        }
        return $tree;
    }

    public static function getByName(string $name){
        return ProductsCategories::whereRaw('UPPER(title) = ?', [$name])->first();
    }
}
