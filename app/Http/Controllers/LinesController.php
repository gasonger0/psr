<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Util;

class LinesController extends Controller
{

    public const LINE_NOT_FOUND = "Такой линии не существует.";
    public const LINE_ALREADY_EXISTS = 'Такая линия уже существует';
    // TODO протестить создание 


    /* CRUD */
    public static function get(Request $request)
    {   
        $result = [];

        Lines::all()->each(function ($line) use (&$result, $request) {
            $extra = LinesExtra::withSession($request)->getOrInsert($line, $request);
            $has_plans = ProductsPlan::withSession($request)->where('line_id', $line->line_id)->count() > 0;

            $result[] = array_merge(
                $line->toArray(), 
                $extra->toArray(), 
                ['has_plans' => $has_plans]
            );
        });

        return Util::successMsg($result);
    }

    public static function create(Request $request)
    {
        $exists = Util::checkDublicate(new Lines(), ['title'], $request->post());
        if ($exists) {
            return Util::errorMsg(self::LINE_ALREADY_EXISTS);
        }
        // Создаём базу
        $id = Lines::insertGetId($request->only((new Lines)->getFillable()));

        if (!$id) {
            return Util::errorMsg($id, 400);
        }
        // Заполняем динию на смену
        $request->merge(['line_id' => $id]);
        $extra_id = LinesExtra::insertGetId($request->only((new LinesExtra)->getFillable()));
        if ($extra_id) {
            return Util::successMsg([
                'line_id' => $id, 
                'line_extra_id' => $extra_id
            ], 201);
        } else {
            return Util::errorMsg($extra_id, 400);
        }
    }
    
    public static function update(Request $request){
        $line = LinesExtra::where('line_id', $request->post('line_id'))->withSession($request)->first();
        if (!$line) {
            return Util::errorMsg(self::LINE_NOT_FOUND, 404);
        }

        $line->update($request->only((new LinesExtra)->getFillable()));
        $line->lines->update($request->only((new Lines)->getFillable()));

        //TODO Обновление слотов сотрудников, графиков и добавлене записи в журнал?
        return Util::successMsg("Линия обновлена");
    }

    public function delete(Request $request) {
        $delete = Lines::find($request->post('line_id'))->delete();
        if ($delete) {
            return Util::successMsg('Линия удалена', 200);
        } else {
            return Util::errorMsg($delete, 400);
        }
    }


    /* ACTIONS */
    public static function down(Request $request) {
        $line = LinesExtra::where('line_id', $request->post('line_id'))
            ->withSession($request)
            ->first();
        $downFrom = $line->down_from;
        if ($downFrom != null) {
            $diff = Carbon::parse($downFrom)->diffInMinutes(new \DateTime());
            $line->down_time += $diff;
            $line->down_from = null;
            $line->save();
            SlotsController::down($request, $downFrom);
        } else {
            $line->down_from = now('Europe/Moscow');
            $line->save();
        }

        return Util::successMsg($downFrom ? 'Простой завершён' :'Простой зафиксирован', 200);
        // TODO Добавить запись в журнал 
    }
}
