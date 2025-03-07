<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\Slots;
use App\Models\Workers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSXGen;

class SlotsController extends Controller
{
    static public function getList()
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        return Slots::where('date', '=', $date)->get()->toJson();
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
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $slot->date = $date;
        $slot->save();

        return $slot->slot_id;
    }

    static public function change(Request $request)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $oldSlot = Slots::where(
            'worker_id',
            '=',
            $request->post('worker_id')
        )->where(
            'line_id',
            '=',
            $request->post('old_line_id')
        )->where(
            'date',
            '=',
            $date
        )->first();

        if (!$oldSlot) {
            $line = Lines::find($request->post('new_line_id'));
            if (!$line) {
                return -1;
            }
            $extra = LinesExtraController::get($line->line_id);
            SlotsController::add(
                $line->line_id,
                $request->worker_id,
                now('Europe/Moscow')->format('H:i:s'),
                $extra->ended_at
            );
            return 0;
        }
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
            if (!isset($r['slot_id']) && $r['new']) {
                $slot = new Slots();
                $slot->line_id = $r['line_id'];
                $slot->started_at = Carbon::parse($r['started_at'])->format('H:i:s'); 
                $slot->ended_at = Carbon::parse($r['ended_at'])->format('H:i:s');
                $slot->worker_id = $r['worker_id'];
                $slot->date = session('date') ?? (new \DateTime())->format('Y-m-d');
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
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        if  ($request->post('delete')){
            Slots::where('worker_id', '=', $id)
                ->where('date', $date)->delete();
        }else {
            $worker = Slots::find($request->post('slot_id'));
            if ($worker) {
                $worker->ended_at = now('Europe/Moscow')->format('H:i:s');
                $worker->save();
            }
        }
        return;
    }

    static public function afterLineUpdate($line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $slots = Slots::where('line_id', '=', $line_id)
            ->where('started_at', '=', $oldStart)
            ->where('date', $date)
            ->get();
        foreach ($slots as $slot) {
            $slot->started_at = $newStart;
            $slot->save();
        }

        $slots = Slots::where('line_id', '=', $line_id)
            ->where('ended_at', '=', $oldEnd)
            ->where('date', $date)
            ->get();
        foreach ($slots as $slot) {
            $slot->ended_at = $newEnd;
            $slot->save();
        }
    }

    static public function down($lineId, $downFrom)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $slots = Slots::where('line_id', '=', $lineId)->where('started_at', '<', $downFrom)->where('date', $date)->get();
        // var_dump($slots, $downFrom, $lineId);
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
            // var_dump($slot->down_time);
            $slot->save();
        }
    }

    public function replace(Request $request)
    {
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

    static public function clear($date = null)
    {
        if (!$date) {
            return;
        }
        Slots::where('date', $date)->each(function($slot) {
            $slot->delete();
        });
    }

    static function print(Request $resuest) {
        $lines = Lines::all()->toArray();
        $titles = call_user_func_array('array_merge', array_map(function($line){
            return [$line['title'], ''];
        }, $lines));
        $columns = [array_merge(['Работник'], $titles)];
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        Workers::all()->each(function($worker) use($lines, &$columns, $date){
            $row = [$worker->title];
            foreach ($lines as $line) {
                $slot = Slots::where('worker_id', '=', $worker->worker_id)
                    ->where('line_id', '=', $line['line_id'])
                    ->where('date', '=', $date)
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
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $name = 'График_' . date('d_m_Y', strtotime($date)) . '.xlsx';
        $file->downloadAs($name);
    }
}
