<?php

namespace App\Http\Controllers;

use App\Models\ProductsSlots;
use App\Util;
use Illuminate\Http\Request;

class ProductsSlotsController extends Controller
{
    public const SLOT_ALREADY_EXISTS = "Такой слот изготовления уже существует";
    public function get(Request $request)
    {
        return ProductsSlots::whereIn('product_id', $request->post())->get();
    }

    public function addSlots(Request $request)
    {
        $keys = ['hardware', 'people_count', 'perfomance'];
        $ret = [];
        foreach ($request->post() as $slot) {
            if ($slot['product_slot_id'] == -1) {
                $s = new ProductsSlots();
                $s->product_id          = $slot['product_id'];
                $s->line_id             = $slot['line_id'];
                $s->people_count        = $slot['people_count'] ?? 0;
                $s->perfomance          = $slot['perfomance'] ?? 0;
                $s->hardware            = $slot['hardware'] ?? 0;
                // $s->order               = $slot['order'];
                $s->type_id             = $slot['type_id'] ?? 1;
                $s->save();
                $ret[] = $s->product_slot_id;
            } else {
                $oldSlot = ProductsSlots::find($slot['product_slot_id']);
                foreach ($keys as $k) {
                    if ($oldSlot->$k != $slot[$k]) {
                        $oldSlot->$k = $slot[$k];
                    }
                }
                $oldSlot->save();
                $ret[] = $oldSlot->product_slot_id;
            }
        }
        return json_encode($ret); 
    }

    public function create(Request $request) {
        $exists = Util::checkDublicate(new ProductsSlots(), [], $request->only((new ProductsSlots())->getFillable()), true);
        if ($exists) {
            return Util::errorMsg(self::SLOT_ALREADY_EXISTS, 400);
        }
        $result = ProductsSlots::create(
            $request->only(
                (new ProductsSlots())->getFillable()
            )
        );
        if ($result) {
            return Util::successMsg($result->toArray(), 201);
        } else {
            return Util::errorMsg($result);
        }
    }
    public function update(Request $request) {
        $data = $request->post();
        foreach ($data as $slot) {
            unset ($slot['isEditing']);
            ProductsSlots::find($slot['product_slot_id'])->first()->update(
                $slot
            );
        }

        return Util::successMsg('Данные обновлены');
    }

    public function delete(Request $request)
    {
        ProductsSlots::find($request->post('product_slot_id'))->delete();
        return Util::successMsg('Этап удалён');
    }

    static public function clear()
    {
        ProductsSlots::truncate();
    }
}
