<?php

namespace App\Http\Controllers;

use App\Models\Hardware;
use App\Models\Lines;
use App\Models\ProductsSlots;
use App\Models\ReturnType;
use App\Models\Setting;
use App\Util;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /* ===== Оборудование ===== */

    public function getHardwares()
    {
        return Util::successMsg(Hardware::orderBy('hardware_id')->get()->toArray());
    }

    public function createHardware(Request $request)
    {
        $hardware = Hardware::create($request->only(['title', 'full_title', 'type']));

        return Util::successMsg($hardware->toArray(), 201);
    }

    public function updateHardware(Request $request)
    {
        $hardware = Hardware::find($request->post('hardware_id'));
        if (! $hardware) {
            return Util::errorMsg('Оборудование не найдено', 404);
        }
        $hardware->update($request->only(['title', 'full_title', 'type']));

        return Util::successMsg($hardware->toArray());
    }

    public function deleteHardware(Request $request)
    {
        $hardware = Hardware::find($request->post('hardware_id'));
        if (! $hardware) {
            return Util::errorMsg('Оборудование не найдено', 404);
        }
        if (ProductsSlots::where('hardware', $hardware->hardware_id)->exists()) {
            return Util::errorMsg('Оборудование используется в слотах продукции');
        }
        $hardware->delete();

        return Util::successMsg('Оборудование удалено');
    }

    /* ===== Возвратные массы ===== */

    public function getReturnTypes()
    {
        return Util::successMsg(ReturnType::orderBy('return_type_id')->get()->toArray());
    }

    public function createReturnType(Request $request)
    {
        $returnType = ReturnType::create($request->only(['title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k']));

        return Util::successMsg($returnType->toArray(), 201);
    }

    public function updateReturnType(Request $request)
    {
        $returnType = ReturnType::find($request->post('return_type_id'));
        if (! $returnType) {
            return Util::errorMsg('Режим не найден', 404);
        }
        $returnType->update($request->only(['title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k']));

        return Util::successMsg($returnType->toArray());
    }

    public function deleteReturnType(Request $request)
    {
        $returnType = ReturnType::find($request->post('return_type_id'));
        if (! $returnType) {
            return Util::errorMsg('Режим не найден', 404);
        }
        if (Lines::where('return_type', $returnType->return_type_id)->exists()) {
            return Util::errorMsg('Режим используется в линиях');
        }
        $returnType->delete();

        return Util::successMsg('Режим удалён');
    }

    /* ===== Постоянные величины ===== */

    private const SETTING_KEYS = ['interval_boil', 'interval_pack', 'zm_perfomance', 'zm_perfomance2'];

    public function getSettings()
    {
        $values = Setting::pluck('value', 'key')->toArray();
        $result = [];
        foreach (self::SETTING_KEYS as $key) {
            $result[$key] = $values[$key] ?? null;
        }

        return Util::successMsg($result);
    }

    public function updateSettings(Request $request)
    {
        foreach (self::SETTING_KEYS as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->post($key)]);
            }
        }

        return Util::successMsg('Настройки сохранены');
    }
}
