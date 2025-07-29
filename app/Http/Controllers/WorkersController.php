<?php

namespace App\Http\Controllers;

use App\Models\Workers;
use App\Models\WorkersBreaks;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Slots;
use App\Util;

class WorkersController extends Controller
{
    public const WORKER_NOT_FOUND = "Такого сотрудника не существует";
    public const WORKER_ALREADY_EXISTS = "Такой сотрудник уже существует";
    public function get(Request $request)
    {
        return Response(Workers::all()->map(function($worker) use ($request) {
            return $worker->toArray() + [
                'break' => WorkersBreaks::getOrInsert($worker, $request)->toArray()
            ];
        }), 200);
    }
    public function create(Request $request)
    {
        $exists = Util::checkDublicate(new Workers(), ['title'], $request->post());
        if ($exists) {
            return Util::errorMsg(self::WORKER_ALREADY_EXISTS, 400);
        }
        $result = Workers::create(
            $request->only(
                (new Workers())->getFillable()
            )
        );
        if ($result) {
            $session = Util::getSessionAsArray($request);
            $date = Carbon::parse($session['date']);
            WorkersBreaks::create([
                'worker_id' => $result->worker_id,
                'started_at' => $date->setTime($session['isDay'] ? 12 : 0,0,0)->format('Y-m-d H:i:s'),
                'ended_at' => $date->copy()->addHour()->format('Y-m-d H:i:s'),
            ] + $session);
            return Util::successMsg($result, 201);
        } else {
            return Util::errorMsg($result);
        }
    }
    public function update(Request $request)
    {
        Workers::find($request->post('worker_id'))->update($request->only((new Workers())->getFillable()));
        if ($break = $request->post('break')) {
            WorkersBreaks::find($break['break_id'])->update($break);
        }
        return Util::successMsg('Данные сотрудника обновлены');

    }
    public function delete(Request $request)
    {
        $result = Workers::find($request->post('worker_id'))->delete();
        if ($result) {
            return Util::successMsg([
                'text' => 'Сотрудник удалён'
            ]);
        } else {
            return Util::errorMsg('Что-то пошло не так');
        }
    }
}
