<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\Slots;
use App\Models\Workers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSXGen;
use App\Util;

class SlotsController extends Controller
{
    public function get(Request $request)
    {
        return Util::successMsg(Slots::withSession($request)->get()->toArray());
    }

    public function create(Request $request)
    {
        $values = $request->post();
        Util::appendSessionToData($values, $request);
        $exists = Util::checkDublicate(new Slots(), [], $values, true);
        if ($exists) {
            return Util::errorMsg('Такой слот уже существует');
        }
        $result = Slots::create($request->only((new Slots())->getFillable()));
        if ($result) {
            return Util::successMsg('Смена создана', 201);
        } else {
            return Util::errorMsg('Произошла ошибка');
        }
    }

    public function change(Request $request)
    {
        $values = $request->post();
        Util::appendSessionToData($values, $request);

        $oldSlot = Slots::withSession($request)
            ->where('worker_id', $values['worker_id'])
            ->where('slot_id', $values['old_slot_id'])
            ->first();

        if (!$oldSlot) {
            return Util::errorMsg('Такого слота не существует', 404);
        }

        $newSlot = Slots::create([
            'line_id' => $request->post('new_line_id'),
            'started_at' => Carbon::now(),
            'worker_id' => $oldSlot->worker_id,
            'ended_at' => $oldSlot->ended_at
        ]);
        $newSlot->save();

        $oldSlot->ended_at = now();
        $oldSlot->save();

        return Util::successMsg($newSlot->toArray(), 201);
    }

    static public function edit(Request $request)
    {
        $data = $request->post();
        if (empty($data)) return 0;
        foreach ($data as $r) {
            if (!isset($r['slot_id']) && $r['new']) {
                $slot = new Slots();
                $slot->line_id = $r['line_id'];
                $slot->started_at = Carbon::parse($r['started_at'])->format('H:i:s'); 
                $slot->ended_at = Carbon::parse($r['ended_at'])->format('H:i:s');
                $slot->worker_id = $r['worker_id'];
                $slot->date = $request->cookie('date');
                $slot->isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
                $diff = (new \DateTime($slot->started_at))->diff(new \DateTime($slot->ended_at));
                $slot->time_planned = $diff->h * 60 + $diff->i;
                $slot->save();
                continue;
            }
            $slot = Slots::find($r['slot_id']);
            $slot->started_at = $r['started_at'];
            $slot->ended_at = $r['ended_at'];
            $slot->save();
            if (!$slot)
                echo 'Ошибка.';
        }
        return 0;
    }

    static public function delete(Request $request)
    {
        if (!($id = $request->post('worker_id'))) return;
        $date = $request->cookie('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        if  ($request->post('delete')){
            Slots::where('worker_id', '=', $id)
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->delete();
        }else {
            $worker = Slots::find($request->post('slot_id'));
            if ($worker) {
                $worker->ended_at = now('Europe/Moscow')->format('H:i:s');
                $worker->save();
            }
        }
        return;
    }

    static public function afterLineUpdate($date, $time, $line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    {
        $slots = Slots::where('line_id', '=', $line_id)
            ->where('started_at', '=', $oldStart)
            ->where('date', $date)
            ->where('isDay', $time)
            ->get();
        foreach ($slots as $slot) {
            $slot->started_at = $newStart;
            $slot->save();
        }

        $slots = Slots::where('line_id', '=', $line_id)
            ->where('ended_at', '=', $oldEnd)
            ->where('date', $date)
            ->where('isDay', $time)
            ->get();
        foreach ($slots as $slot) {
            $slot->ended_at = $newEnd;
            $slot->save();
        }
    }

    // +
    public static function down(Request $request, string $downFrom)
    {
        $slots = Slots::where('line_id', '=', $request->post('line_id'))
            ->where('started_at', '<', $downFrom)
            ->withSession($request)->get();

        foreach ($slots as $slot) {
            if ($slot->ended_at < now('Europe/Moscow')) {
                // Если простой кончился после окончания слота, т.е. линия стояла до конца рабочей смены
                $diff = (new \DateTime(now('Europe/Moscow')))
                    ->diff(new \DateTime($slot->ended_at));
                $slot->down_time =+ $diff->h * 60 + $diff->i;
            } else {
                //Если простой кончился до окончания слота
                $diff = (new \DateTime(now('Europe/Moscow')))->diff(new \DateTime($downFrom));
                $slot->down_time += $diff->h * 60 + $diff->i;
            }
            $slot->save();
        }
        return;
    }

    public function replace(Request $request)
    {
        $data = $request->post();
        $oldSlot = Slots::find($data['slot_id']);
        $oldEnd = $oldSlot->ended_at;
        $oldSlot->ended_at = now('Europe/Moscow')->format('H:i:s');
        $oldSlot->save();

        return SlotsController::add(
            $request->cookie('date'),
            filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN),
            $oldSlot->line_id,
            $data['new_worker_id'],
            now('Europe/Moscow')->format('H:i:s'),
            $oldEnd
        );
    }

    static public function clear($date = null, $isDay) 
    {
        if (!$date) {
            return;
        }
        Slots::where('date', $date)->where('isDay', $isDay)->each(function($slot) {
            $slot->delete();
        });
    }

    static function print(Request $request) {
        $lines = Lines::all()->toArray();
        $titles = call_user_func_array('array_merge', array_map(function($line){
            return [$line['title'], ''];
        }, $lines));
        $columns = [array_merge(['Работник'], $titles)];
        $date = $request->cookie('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        Workers::all()->each(function($worker) use($lines, &$columns, $date, $isDay){
            $row = [$worker->title];
            foreach ($lines as $line) {
                $slot = Slots::where('worker_id', '=', $worker->worker_id)
                    ->where('line_id', '=', $line['line_id'])
                    ->where('date', '=', $date)
                    ->where('isDay', '=', $isDay)
                    ->first();
                if ($slot) {
                    $row[] = $slot->started_at;
                    $row[] = $slot->ended_at;
                } else {
                    $row[] = '';
                    $row[] = '';
                }
            }
            $itemsCount = array_filter(array_slice($row, 1), function($value) {
                return $value != '';
            });
            if (count($itemsCount) > 0){
                $columns[] = $row;
            }
        });
        
        // Post-processing columns
        $i = 1;
        while ($i < count($columns[0])) {
            $isEmpty = array_filter(array_column($columns, $i), function($value) {
                return $value != '';
            });
            if (count($isEmpty) <= 1) {
                array_walk($columns, function(&$row) use($i) {
                    array_splice($row, $i, 2);
                });
            } else {
                $i+=2;
            }
        }
        $file = SimpleXLSXGen::fromArray($columns);
        $i = 1;
        $a = 95; // Заглавная А
        // var_dump(count($columns[0]));
        while ($i < count($columns[0])) {
            $mergeRange = chr($a + $i) . "1:". chr($a + $i + 1) . '1';
            $file->mergeCells($mergeRange);
            // var_dump($mergeRange);
            $i+=2;
        }
        $name = 'График_' . date('d_m_Y', strtotime($date)) . '.xlsx';
        $file->downloadAs($name);
    }
}
