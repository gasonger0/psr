<?php

namespace App\Http\Controllers;

use App\Models\Workers;
use Illuminate\Http\Request;
use App\Models\Slots;
use App\Util;

class WorkersController extends Controller
{
    public const WORKER_NOT_FOUND = "Такого сотрудника не существует";
    public const WORKER_ALREADY_EXISTS = "Такой сотрудник уже существует";
    public function get()
    {
        return Response(Workers::all()->toArray(), 200);
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
            return Util::successMsg($result, 201);
        } else {
            return Util::errorMsg($result);
        }
    }
    public function update(Request $request)
    {
        Workers::update($request->only((new Workers())->getFillable()));
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

    // TODO депрекейтед возможно
    public function change(Request $request)
    {
        try {
            foreach ($request->post() as $worker) {
                if ($worker['slot_id']) {
                    $slot = Slots::find($worker['slot_id']);
                    $slot->line_id = $worker['line_id'];
                    $slot->save();
                }
            }
            return Util::successMsg('Сотрудники изменены', 200);
        } catch (\Exception $e) {
            return Util::errorMsg($e->getMessage(), 500);
        }
    }
}
