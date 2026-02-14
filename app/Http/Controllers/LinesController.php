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


    /* CRUD */
    public function get(Request $request)
    {
        $result = [];
        $session = Util::getSessionAsArray($request);

        // Оптимизация: загружаем все линии с нужными отношениями одним запросом
        Lines::with([
            'plans' => function ($query) use ($session) {
                $query->where('date', $session['date'])
                    ->where('isDay', $session['isDay']);
            },
            'linesExtra'
        ])->chunk(200, function ($lines) use (&$result, $request) {
            foreach ($lines as $line) {
                $extra = LinesExtra::withSession($request)->getOrInsert($line, $request);

                $has_plans = $line->plans->isNotEmpty();

                $result[] = array_merge(
                    $line->makeHidden('plans', 'linesExtra')->toArray(), // Скрываем plans из результата
                    $extra->toArray(),
                    ['has_plans' => $has_plans]
                );
            }
        });

        return Util::successMsg($result);
    }

    public function create(Request $request)
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

    public function update(Request $request)
    {
        $line = LinesExtra::find($request->post('line_extra_id'))->first();
        if (!$line) {
            return Util::errorMsg(self::LINE_NOT_FOUND, 404);
        }

        $old = $line->toArray();

        $line->update($request->only((new LinesExtra)->getFillable()));
        $line->lines->update($request->only((new Lines)->getFillable()));

        $log = null;
        if ($request->post('cancel_reason')) {
            $log = LogsController::create([
                'line_id' => $line->line_id,
                'action' => 'Перенос начала работы линии по причине: ' . $request->post('cancel_reason'),
                'started_at' => $old['started_at'],
                'ended_at' => Util::getCurrentTime($request)
            ] + Util::getSessionAsArray($request));
        }
        SlotsController::afterLineUpdate($request, $old);
        return Util::successMsg($log ? $log : []);
    }

    public function delete(Request $request)
    {
        $delete = Lines::find($request->post('line_id'))->delete();
        if ($delete) {
            return Util::successMsg('Линия удалена', 200);
        } else {
            return Util::errorMsg($delete, 400);
        }
    }


    /* ACTIONS */
    public static function down(Request $request)
    {
        $downTime = Util::getCurrentTime($request);

        $line = LinesExtra::where('line_id', $request->post('line_id'))
            ->withSession($request)
            ->first();
        $downFrom = $line->down_from;
        if ($downFrom != null) {
            $diff = Carbon::parse($downFrom)->diffInMinutes($downTime);
            $line->down_time += $diff;
            $line->down_from = null;
            $line->save();
            SlotsController::down($request, $downFrom);
        } else {
            $line->down_from = $downTime->format('Y-m-d H:i:s');
            $line->save();
        }

        $logData = [
            'line_id' => $line->line_id,
            'action' => "Остановка работы линии по причине: " . $request->post('reason'),
        ] + Util::getSessionAsArray($request) +
        ($downFrom ? ["ended_at" => $downTime->format('Y-m-d H:i:s')] : 
            ["started_at" => $downTime->format('Y-m-d H:i:s')]);

        $log = $downFrom ? LogsController::update($logData) : LogsController::create($logData);

        return Util::successMsg($log, 200);
    }

    public static function updateLinesTime(array $plansOrder)
    {
        foreach ($plansOrder as $line_id => $plans) {
            $firstPlan = reset($plans);
            $start = Carbon::parse($firstPlan['started_at']);
            $end = Carbon::parse(end($plans)['ended_at']);

            LinesExtra::where('line_id', $line_id)
                ->where('isDay', $firstPlan['isDay'])
                ->where('date', $firstPlan['date'])
                ->get()->each(function ($line) use ($start, $end) {
                    $line->update([
                        'started_at' => $start->addMinutes(-$line->prep_time),
                        'ended_at' => $end->addMinutes($line->after_time)
                    ]);
                });
        }
    }
}
