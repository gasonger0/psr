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
    /* CRUD */
    public function get(Request $request)
    {
        return Util::successMsg(Slots::withSession($request)->get()->toArray());
    }

    public function create(Request $request)
    {
        Util::appendSessionToData($request);
        $exists = Util::checkDublicate(new Slots(), [], $request->only((new Slots())->getFillable()), true);
        if ($exists) {
            return Util::errorMsg('Такой слот уже существует');
        }
        $result = Slots::create($request->only((new Slots())->getFillable()));
        if ($result) {
            return Util::successMsg($result->toArray());
        } else {
            return Util::errorMsg('Произошла ошибка');
        }
    }

    public function update(Request $request)
    {
        Util::appendSessionToData($request);
        $result = Slots::find($request->post('slot_id'))->update($request->only((new Slots())->getFillable()));
        if ($result) {
            return Util::successMsg('Обновлено');
        } else {
            return Util::errorMsg('Произошла ошибка');
        }
    }

    public function delete(Request $request)
    {
        if ($request->post('delete')) {
            // Досрочное окончание
            Slots::find($request->post('slot_id'))->update([
                'ended_at' => Carbon::now('Europe/Moscow')
            ]);
        } else {
            // не вышел на работу
            Slots::find($request->post('slot_id'))->delete();
        }
        return Util::successMsg('Смена сотрудника звершена', 200);
    }

    /* ACTIONS */
    public function change(Request $request)
    {
        Util::appendSessionToData($request);

        $oldSlot = Slots::find($request->post('old_slot_id'))->first();

        if (!$oldSlot) {
            return Util::errorMsg('Такого слота не существует', 404);
        }

        $oldSlot->update([
            'ended_at' => Carbon::now('Europe/Moscow')
        ]);

        $request->merge([
            'line_id' => $request->post('new_line_id'),
            'started_at' => Carbon::now('Europe/Moscow'),
            'worker_id' => $oldSlot->worker_id,
            'ended_at' => $oldSlot->ended_at
        ]);

        $request->request->remove('new_worker_id');
        $request->request->remove('slot_id');

        return $this->create($request);
    }

    public static function afterLineUpdate(Request $request, array $old)
    {
        $slots = Slots::withSession($request)
            ->where('line_id', $request->post('line_id'))->get();

        $slots->where('started_at', $old['started_at'])->each(function ($s) use ($request) {
            $s->update([
                'started_at' => $request->post('started_at')
            ]);
        });
        $slots->where('ended_at', $old['ended_at'])->each(function ($s) use ($request) {
            $s->update([
                'ended_at' => $request->post('ended_at')
            ]);
        });
        return;
    }

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
                $slot->down_time = +$diff->h * 60 + $diff->i;
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
        $oldSlot = Slots::find($request->post('slot_id'));
        $oldEnd = $oldSlot->ended_at;
        $oldSlot->ended_at = now('Europe/Moscow');
        $oldSlot->save();

        $request->merge([
            'started_at' => now('Europe/Moscow'),
            'ended_at' => $oldEnd,
            'line_id' => $oldSlot->line_id,
            'worker_id' => $request->post('new_worker_id'),
        ]);
        $request->request->remove('new_worker_id');
        $request->request->remove('slot_id');

        return $this->create($request);
    }

    public function clear($date = null, $isDay)
    {
        // TODO А надо ли оно тут?
        if (!$date) {
            return;
        }
        Slots::where('date', $date)->where('isDay', $isDay)->each(function ($slot) {
            $slot->delete();
        });
    }

    public function print(Request $request)
    {
        // Создаём шапку для таблицы
        $lines = Lines::all(['title', 'line_id']);
        $columns = [['Компания', 'Работник']];
        $lines->each(function ($line) use (&$columns) {
            array_push($columns[0], $line->title, '');
        });


        // Заполняем строки 
        Workers::all()->each(function ($worker) use ($lines, &$columns, $request) {
            $row = [$worker->company, $worker->title];
            $lines->each(function ($line) use ($request, $worker, &$row) {
                $slot = Slots::withSession($request)
                    ->where('line_id', $line->line_id)
                    ->where('worker_id', $worker->worker_id)->first();
                if ($slot) {
                    array_push(
                        $row,
                        Carbon::parse($slot->started_at)->format('H:i:s'),
                        Carbon::parse($slot->ended_at)->format('H:i:s')
                    );
                } else {
                    array_push($row, '', '');
                }
            });
            $itemsCount = array_filter(array_slice($row, 2), function ($value) {
                return $value != '';
            });
            if (count($itemsCount) > 0) {
                $columns[] = $row;
            }
        });

        $i = 2;
        while ($i < count($columns[0])) {
            $isEmpty = array_filter(array_column($columns, $i), function ($value) {
                return $value != '';
            });
            if (count($isEmpty) <= 1) {
                array_walk($columns, function (&$row) use ($i) {
                    array_splice($row, $i, 2);
                });
            } else {
                $i += 2;
            }
        }

        // Пост обработка по мёржу ячеек
        $file = SimpleXLSXGen::fromArray($columns);
        $i = 4;
        $a = 95; // Заглавная А

        while ($i <= count($columns[0])) {
            $mergeRange = chr($a + $i) . "1:" . chr($a + $i + 1) . '1';
            $file->mergeCells($mergeRange);

            $i += 2;
        }
        $date = Carbon::parse($request->attributes->get('date'))->format('Y_m_d');
        $isDay = $request->attributes->get('isDay') ? 'День' : 'Ночь';
        $name = "График_$date-$isDay.xlsx";
        $file->downloadAs($name);
    }
}
