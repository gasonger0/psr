<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Util;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    public const COMPANY_ALREADY_EXISTS = "Такая компания уже существует.";
    public const COMPANY_NOT_FOUND = "Такой компании нет.";

    public function get()
    {
        return Util::successMsg(Companies::all()->toArray());
    }

    public static function create(Request $request)
    {
        $exists = Util::checkDublicate(new Companies(), ['title'], $request->post());
        if ($exists) {
            return Util::errorMsg(self::COMPANY_ALREADY_EXISTS, 400);
        }

        return Util::successMsg(
            Companies::create(
                $request->post()
            ),
            201
        );
    }

    public static function update(Request $request)
    {
        $model = Companies::find($request->post('company_id'));
        if (!$model) {
            return Util::errorMsg(self::COMPANY_NOT_FOUND, 404);
        }
        $model->update($request->post());
        return Util::successMsg('Данные обновлены', 200);
    }

    public static function delete(Request $request)
    {
        $model = Companies::where('company_id', $request->post('company_id'))->get();
        if (!$model) {
            return Util::errorMsg(self::COMPANY_NOT_FOUND, 404);
        }
        $model->delete();

        return Util::errorMsg("Компания удалена.");
    }
}
