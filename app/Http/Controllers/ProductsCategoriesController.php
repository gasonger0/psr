<?php

namespace App\Http\Controllers;

use App\Models\Products_categories;


class ProductsCategoriesController extends Controller
{
    public function getTree() {
        $data = Products_categories::all()->toArray();
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
}
