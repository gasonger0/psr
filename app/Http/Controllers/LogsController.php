<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\Slots;
use App\Util;
use Illuminate\Http\Request;
use App\Models\Logs;
use Carbon\Carbon;
use Shuchkin\SimpleXLSXGen;
use App\Models\Lines;
use App\Models\Workers;
use function PHPUnit\Framework\returnArgument;

class LogsController extends Controller
{
    public static function create(array $data)
    {
        $workers = Slots::withSession($data)
            ->where('line_id', $data['line_id'])
            ->where('started_at', '<=', $data['started_at'])
            ->where('ended_at', '>=', $data['ended_at'] ?? $data['started_at'])
            ->get()
            ->map(function ($i) {
                return $i->worker_id;
            })->toArray();

        $log = Logs::create($data + [
            'people_count' => count($workers),
            'workers' => implode(',', $workers),
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'] ?? null
        ]);
        return $log->toArray();
    }

    public static function update(array $data)
    {
        $log = Logs::withSession($data)->where('line_id', $data['line_id'])->where('ended_at', null)->first();
        if ($log) {
            $log->update($data);
        }
        return 'Простой завершён';
    }

    public function get(Request $request)
    {
        return Util::successMsg(Logs::withSession($request)->get()->toArray());
    }

    public function print(Request $request)
    {
        /* 1) просто выводим журнал
         2) Обходим записи по порядку и обрабатываем как:
            - Получаем список компаний через Compaines::get()
            - Парсим время простоя, считаем его длительность
            - Берём список сотрудников простоя и по компаниям перемножаем на длительности
         */

        // $columns = [[
        //     'Стоимость простоя',
        //     '220'
        // ], []];
        $columns =
            [[
                'Линия',
                'Начат',
                'Окончен',
                'Кол-во человек на линии',
                'Итого часов простоев',
                'Стоимость',
                'Причина'
        ]];
        $companies = [];
        $lines = [];
        Logs::withSession($request)
            ->with('line')
            ->orderBy('started_at', 'ASC')
            ->each(function ($el) use (&$columns, &$companies, $request, &$lines) {
                $count = count($columns)+1;
                $columns[] = [
                    $el->line->title,
                    Carbon::parse($el->started_at)->format('H:i:s'),
                    Carbon::parse($el->ended_at)->format('H:i:s'),
                    $el->people_count,
                    "<f>=МИНУТЫ(C$count-B$count)/60*D$count",
                    "<f>=B1*E$count",
                    explode(": ", $el->action)[1]
                ];

                if (!isset($lines[ $el->line_id ])) {
                    $lines[$el->line_id] = [
                        'title' => $el->line->title,
                        'hours' => [],
                        'cost' => []
                    ];
                }
                $lines[ $el->line_id]['hours'][] = "E$count";
                // $lines[ $el->line_id]['cost'][] = "F$count";

                $durationByLine = Carbon::parse($el->started_at)->diffInHours(Carbon::parse($el->ended_at));
                if ($el->workers == '' || $el->workers == null) {
                    return;
                }

                Slots::where('line_id', $el->line_id)
                    ->withSession($request)
                    ->whereIn('worker_id', explode(',', $el->workers))
                    ->each(function ($slot) use (&$companies, $el, $durationByLine, &$lines) {
                        $worker = Workers::find($slot->worker_id);
                        $company = Companies::where('company_id', $worker->company_id)->first();

                        if (!isset($companies[$company->company_id])) {
                            $companies[$company->company_id] = [
                                'title' => $company->title,
                                'hours' => 0,
                                'people' => 0
                            ];
                        }
                        /*
                        Проверка простоя и слота на соприкосновение:
                        1) Если простой кончился раньше слота - скипаем
                        2) Если простой начался во время слота и кончился во время слота, простой фиксируем
                        3) Если простой начался во время слота и кончился позже, то простой фиксируем от его начала до конца слота!
                        4) Если прсотой начался раньше слота, но кончился во время слота, фиксируем 
                        */
                        if ($slot->started_at >= $el->ended_at) {
                            return;
                        // } else if ($slot->started_at <= $el->started_at && $slot->ended_at >= $el->ended_at) {
                        //     // $durationByLine = $durationByLine;
                        } else if ($slot->started_at >= $el->started_at && $slot->ended_at >= $el->ended_at) {
                            $durationByLine = abs(Carbon::parse($slot->started_at)->diffInHours(Carbon::parse($el->ended_at)));
                        } else if ($slot->started_at <= $el->started_at && $slot->ended_at < $el->ended_at) {
                            $durationByLine = abs(Carbon::parse($slot->ended_at)->diffInHours(Carbon::parse($el->started_at)));
                        }

                        $companies[$company->company_id]['hours'] += $durationByLine;
                        $companies[$company->company_id]['people'] += 1;
                        
                        // Простой на линии = сумма простоев 
                        $lines[ $el->line_id]['cost'][] = "$company->stay_cost * $durationByLine";
                    });
            });

        array_push($columns, [], ["ИТОГО ПО ЛИНИЯМ", "ЧАС", "СТОИМОСТЬ"]);
        foreach ($lines as $line) {
            $columns[] = [
                $line['title'],
                "<f>=".implode("+", $line['hours']),
                "<f>=".implode("+", $line['cost'])
            ];
        }

        array_push($columns, [], ['КОМПАНИИ', 'ЧЕЛ-ЧАС']);
        foreach ($companies as $company) {
            $columns[] = [
                $company['title'],
                $company['hours']
            ];
        }

        array_push($columns, [],[],
        ["Начальник смены"], 
        [],
        ["Мастер"], 
        [],
        ["Начальник производства"], 
        [],
        ["Главный инженер"]
        );

        $session = Util::getSessionAsArray($request);
        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = "Простои_$session[date]_" . ($session['isDay'] ? 'День' : 'Ночь') . ".xlsx";
        $xlsx->downloadAs($name);
        // return $name;
    }
}
