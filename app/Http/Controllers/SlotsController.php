<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\Slots;
use App\Models\Workers;
use App\Models\WorkersBreaks;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
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

        // $exists = Util::checkDublicate(new Slots(), [], $request->only((new Slots())->getFillable()), true);
        // if ($exists) {
        //     return Util::errorMsg('Такой слот уже существует');
        // }
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
            // не вышел на работу
            Slots::find($request->post('slot_id'))->delete();
        } else {
            // Досрочное окончание
            Slots::find($request->post('slot_id'))->update([
                'ended_at' => Util::getCurrentTime($request)
            ]);
        }
        return Util::successMsg('Смена сотрудника звершена', 200);
    }

    /* ACTIONS */
    public function change(Request $request)
    {
        $cookie = Util::getSessionAsArray($request);

        $oldSlot = Slots::find($request->post('old_slot_id'));

        if (!$oldSlot) {
            return Util::errorMsg('Такого слота не существует', 404);
        }

        $newEnd = Util::getCurrentTime($request);

        $data = new Request([
            'worker_id' => $oldSlot->worker_id,
            'line_id' => $request->post('new_line_id'),
            'started_at' => $newEnd->format("Y-m-d H:i:s"),
            'ended_at' => Carbon::parse($oldSlot->ended_at)->format("Y-m-d H:i:s")
        ] + $cookie, [], $cookie);

        // $data->cookie('date', $cookie['date']);
        // $data->cookie('isDay', $cookie['isDay']);

        $oldSlot->update([
            'ended_at' => $newEnd->format("Y-m-d H:i:s")
        ]);

        return $this->create($data);
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
        $oldEnd = Carbon::parse($oldSlot->ended_at);

        $newEnd = Util::getCurrentTime($request);

        $oldSlot->ended_at = $newEnd->format("Y-m-d H:i:s");
        $oldSlot->save();
        $request->merge([
            'started_at' => $newEnd->format("Y-m-d H:i:s"),
            'ended_at' => $oldEnd->format("Y-m-d H:i:s"),
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
        $lines = Lines::select(['title', 'line_id'])
            ->with([
                "linesExtra" => function (Builder $query) use ($request) {
                    $query->withSession($request);
                }
            ])
            ->whereIn(
                'line_id',
                Slots::withSession($request)
                    ->get('line_id')
            )->get();
        $border = "border=\"none none medium#313131 none\"";
        $center1 = "<middle><center>";
        $center2 = "</middle></center>";

        $columns = [
            [
                "<style>Компания</style>",
                "<style>СПИСОК РАБОЧИХ</style>",
                "<style>$center1 Обед$center2</style>",
                "<style></style>"
            ],
            [
                '',
                '',
                "$center1 начало $center2",
                "$center1 конец $center2"
            ],
            array_fill(0, 4, "<style $border></style>")
        ];

        $lines->each(function ($line) use (&$columns, $border, $center1, $center2) {
            array_push($columns[0], "<style height=\"30\"><wraptext>$center1 $line->title $center2</wraptext></style>", "<style></style>");
            array_push($columns[1], "$center1 начало $center2", "$center1 конец $center2");
            array_push(
                $columns[2],
                "<style $border>" .
                Carbon::parse($line->linesExtra[0]->started_at)->format('H:i:s') .
                "</style",
                "<style $border>" .
                Carbon::parse($line->linesExtra[0]->ended_at)->format('H:i:s') .
                "</style>"
            );
        });

        // Заполняем строки 
        Workers::all()->each(function ($worker) use ($lines, &$columns, $request, $border) {
            $lunch = WorkersBreaks::withSession($request)
                ->where('worker_id', $worker->worker_id)
                ->first();
            $workerRows = [
                [
                    Companies::find($worker->company_id)->title,
                    explode(" ", $worker->title)[0],
                    Carbon::parse($lunch->started_at)->format('H:i:s'),
                    Carbon::parse($lunch->ended_at)->format('H:i:s')
                ]
            ];
            $lines->each(function ($line) use ($request, $worker, &$workerRows) {
                $slots = Slots::withSession($request)
                    ->where('line_id', $line->line_id)
                    ->where('worker_id', $worker->worker_id);
                if ($slots) {
                    $slots->each(function ($sl, $k) use (&$workerRows) {
                        if (!isset($workerRows[$k])) {
                            $workerRows[$k] = array_fill(0, count($workerRows[0]) - 2, '');
                        }
                        array_push(
                            $workerRows[$k],
                            Carbon::parse($sl->started_at)->format('H:i:s'),
                            Carbon::parse($sl->ended_at)->format('H:i:s')
                        );
                    });

                    // Проверка, что соответсвует кол-во строк
                    $maxCount = max(array_map(fn($r) => count($r), $workerRows));
                    foreach ($workerRows as &$row) {
                        if (count($row) < $maxCount) {
                            $counts = $maxCount - count($row);
                            $row = array_merge($row, array_fill(0, $counts, ""));
                        }
                    }
                } else {
                    foreach ($workerRows as &$r) {
                        array_push($r, "", "");
                    }
                }
            });

            $itemsCount = array_filter($workerRows, function (&$value) use ($columns) {
                return count($value) > 4;
            });
            if (count($itemsCount) > 0) {
                // Делаем нижнюю границу
                $lastIndex = count($workerRows) - 1;
                if (count($workerRows[$lastIndex]) < count($columns[0])) {
                    $linesCount = count($workerRows[$lastIndex]);
                    $workerRows[$lastIndex] = array_merge(
                        $workerRows[$lastIndex],
                        array_fill(0, count($columns[0]) - $linesCount, "")
                    );
                }
                $workerRows[$lastIndex] = array_map(fn($i) => "<style $border>$i</style>", $workerRows[$lastIndex]);
                array_push($columns, ...$workerRows);
            }
        });

        // Пост обработка по мёржу ячеек
        $file = SimpleXLSXGen::fromArray($columns);
        $i = 2;
        $a = 97; // Заглавная А

        while ($i < count($columns[0])) {
            $mergeRange = chr($a + $i) . "1:" . chr($a + $i + 1) . '1';
            $file->mergeCells($mergeRange);
            $file->setColWidth($i, 10)->setColWidth($i + 1, 10);
            $i += 2;
        }

        $file
            ->setColWidth(1, 10)
            ->setColWidth(2, 30);

        $date = Carbon::parse($request->attributes->get('date'))->format('Y_m_d');
        $isDay = $request->attributes->get('isDay') ? 'День' : 'Ночь';
        $name = "График_$date-$isDay.xlsx";
        $file->downloadAs($name);
    }
}
