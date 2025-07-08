<?php

namespace App\Http\Controllers;

use App\Errors;
use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\Slots;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Response;

class LinesController extends Controller
{

    const LINE_NOT_FOUND = "Такой линии не существует.";
    // TODO протестить создание 


    /* CRUD */
    public static function get(Request $request)
    {   
        $result = [];

        Lines::all()->each(function ($line) use (&$result, $request) {
            $extra = LinesExtra::withSession($request)->getOrInsert($line);
            $has_plans = ProductsPlan::withSession($request)->where('line_id', $line->line_id)->count() > 0;

            $result[] = array_merge(
                $line->toArray(), 
                $extra->toArray(), 
                ['has_plans' => $has_plans]
            );
        });

        return Response($result, 200);
    }

    public static function add(Request $request)
    {
        // Создаём базу
        $id = Lines::insertGetId($request->only((new Lines)->getFillable()));

        // Заполняем динию на смену
        $request->merge(['line_id' => $id]);
        $extra_id = LinesExtra::insertGetId($request->only((new LinesExtra)->getFillable()));
        return Response([
            'line_id' => $id, 
            'line_extra_id' => $extra_id
        ], 201);
    }
    
    public static function update(Request $request){
        if (!$request->post('line_id')) {
            return Response(['error' => self::LINE_NOT_FOUND], 404);
        }

        $line = LinesExtra::where('line_id', $request->post('line_id'))->withSession($request)->first();

        $line->update($request->only((new LinesExtra)->getFillable()));
        $line->lines->update($request->only((new Lines)->getFillable()));

        //TODO Обновление слотов сотрудников, графиков и добавлене записи в журнал?
        return Response([
            'message' => [
                'type' => 'success',
                'title' => "Линия обновлена"
            ]
        ], 200);
    }

    public static function save(Request $request)
    {
        if ($request->post('line_id') == -1) {
            $id = self::add($request->cookie('date'), filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN),$request->post());
            if ($id) {
                return json_encode([
                    "success" => true,
                    'msg' => 'Линия № ' . $id . ' успешно добавлена'
                ]);
            } else {
                return json_encode([
                    "success" => 'false'
                ]);
            }
        }
        $line = Lines::find($request->post('line_id'));
        if ($line) {
            $d = LinesExtraController::update($request->cookie('date'), filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN), $line->line_id, $request->post());
            $start = $d['start'];
            $end = $d['end'];
            $line->color = $request->post('color');
            $line->type_id = $request->post('type_id');
            $line->title = $request->post('title');
            $line->save();

            if ($request->post('started_at')) {
                SlotsController::afterLineUpdate(
                    $request->cookie('date'),
                    filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN),
                    $request->post('line_id'),
                    $request->post('started_at'),
                    $start,
                    $request->post('ended_at'),
                    $end
                );
                // ProductsPlanController::afterLineUpdate($request->post('line_id'), $request->post('started_at'), $start, $request->post('ended_at'), $end);
                if ($request->post('cancel_reason') != null) {
                    $d = Slots::where('line_id', '=', $line->line_id)->where('started_at', '<=', $start)->get('worker_id')->toArray();
                    $d = array_map(function ($a) {
                        return $a['worker_id'];
                    }, $d);
                    $request = new Request([],[
                        'line_id' => $line->line_id,
                        'action' => 'Перенос времени работы линии',
                        'extra' => 'Причина: ' . $request->post('cancel_reason_extra') . PHP_EOL . 'Старое время: ' . $start,
                        'people_count' => $request->post('workers_count'),
                        'type' => 3,
                        'workers' => implode(',', $d),
                    ],[],['date' => $request->cookie('date'), 'isDay' => filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN)]);
                    LogsController::add($request);
                }
            }
            return json_encode([
                "success" => true
            ]);
        }
    }

    public function delete(Request $request) {
        $line_id = $request->post('line_id');
        Lines::where('line_id', $line_id)->delete();
        LinesExtra::where('line_id', $line_id)->delete();
        return Response(['message' => [
            'type'      => 'success',
            'title'   => 'Линия удалена'
        ]], 200);
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

        return Response([
            'message' => [
                'type'  => 'success',
                'title' => $downFrom ? 'Простой завершён' :'Простой зафиксирован'
            ]
        ], 200);
        // TODO Добавить запись в журнал 
    }
}
