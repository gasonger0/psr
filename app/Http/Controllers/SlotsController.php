<?php

namespace App\Http\Controllers;

use App\Models\Slots;
use Illuminate\Http\Request;

class SlotsController extends Controller
{
    public function getList()
    {
        return Slots::all()->toJson();
    }

    static public function add($line_id = null, $worker_id = null, $start = null, $end = null)
    {
        if (!$line_id)
            return;
        $slot = new Slots();


        $slot->line_id = $line_id;
        $slot->started_at = $start;
        $slot->ended_at = $end;
        $slot->worker_id = $worker_id;
        $diff = (new \DateTime($start))->diff(new \DateTime($end));
        $slot->time_planned = $diff->h * 60 + $diff->i;
        $slot->save();

        return $slot->slot_id;

    }

    static public function change(Request $request)
    {
        $oldSlot = Slots::where(
            'worker_id',
            '=',
            $request->post('worker_id')
        )->where(
                'line_id',
                '=',
                $request->post('old_line_id')
            )->first();

        if (!$oldSlot)
            return -1;
        $endTime = null;

        $endTime = $oldSlot->ended_at;
        $oldSlot->ended_at = now('Europe/Moscow')->format('H:i:s');
        $oldSlot->save();

        $newSlot = new Slots;
        $newSlot->line_id = $request->post('new_line_id');
        $newSlot->started_at = now('Europe/Moscow')->format('H:i:s');
        $newSlot->worker_id = $request->post('worker_id');
        $newSlot->ended_at = $endTime;

        $diff = (new \DateTime($newSlot->started_at))->diff(new \DateTime($endTime));
        $newSlot->time_planned = $diff->h * 60 + $diff->i;

        $newSlot->save();

        return 0;
    }

    static public function edit(Request $request)
    {
        $data = $request->post();
        if (empty($data)) return 0;
        foreach ($data as $r) {
            $slot = Slots::find($r['slot_id']);
            $slot->started_at = $r['started_at'];
            $slot->ended_at = $r['ended_at'];
            $slot->save();
            if (!$slot)
                echo 'Ошибка.';
        }
        return 0;
    }

    static public function delete(Request $request) {
        if (!($id = $request->post('worker_id'))) return;
        Slots::where('worker_id', '=', $id)->delete();
        return;
    }

    static public function afterLineUpdate(int $line_id, int $timeshift) {
        $slots = Slots::where('line_id', '=', $line_id)->get();
        foreach ($slots as $slot) {
            $slot->addMinutes($timeshift);
        }
    }

    static public function down($lineId, $downFrom) {
        $slots = Slots::where('line_id', '=', $lineId)->where('started_at', '<', $downFrom)->get();
        var_dump($slots, $downFrom, $lineId);
        foreach ($slots as $slot) {
            if ($slot->ended_at < now('Europe/Moscow')) {
                // Если простой кончился после окончания слота, т.е. линия стояла до конца рабочей смены
                $diff = (new \DateTime(now('Europe/Moscow')))->diff(new \DateTime($slot->ended_at));
                $slot->down_time = $slot->down_time + ($diff->h * 60 + $diff->i);
            } else {
                //Если простой кончился до окончания слота
                $diff = (new \DateTime(now('Europe/Moscow')))->diff(new \DateTime($downFrom));
                $slot->down_time = $slot->down_time + ($diff->h * 60 + $diff->i);
            }
            var_dump($slot->down_time);
            $slot->save();
        }
    }

    public function replace(Request $request) {
        $data = $request->post();
        $oldSlot = Slots::find($data['slot_id']);
        $oldEnd = $oldSlot->ended_at;
        $oldSlot->ended_at = now('Europe/Moscow')->format('H:i:s');
        $oldSlot->save();

        return SlotsController::add(
            $oldSlot->line_id,
            $data['new_worker_id'],
            now('Europe/Moscow')->format('H:i:s'),
            $oldEnd
        );
    }

    static public function dropData()
    {
        return Slots::truncate();
    }
}
