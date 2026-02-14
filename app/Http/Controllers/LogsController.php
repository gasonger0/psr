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
        $columns = [
            [
                'ИД',
                'Линия',
                'Начат',
                'Окончен',
                'Кол-во человек на линии'
            ]
        ];
        $companies = [];
        Logs::withSession($request)
            ->orderBy('started_at', 'ASC')
            ->each(function ($el) use (&$columns, &$companies, $request) {
                $columns[] = [
                    $el->log_id,
                    $el->line,
                    Carbon::parse($el->started_at)->format('H:i:s'),
                    Carbon::parse($el->ended_at)->format('H:i:s'),
                    $el->people_count
                ];

                $durationByLine = Carbon::parse($el->started_at)->diffInHours(Carbon::parse($el->ended_at));
                if ($el->workers == '' || $el->workers == null) {
                    return;
                }

                $workerSlots = Slots::where('line_id', $el->line_id)
                    ->withSession($request)
                    ->whereIn('worker_id', explode(',', $el->workers))
                    ->each(function ($slot) use (&$companies, $el, $durationByLine) {
                        $worker = Workers::find($slot->worker_id);
                        $company = Companies::where('company_id', $worker->company_id)->first();

                        if (!isset($companies[$company->company_id])) {
                            $companies[$company->company_id] = [
                                'title' => $company->title,
                                'hours' => 0
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
                        } else if ($slot->started_at <= $el->started_at && $slot->ended_at >= $el->ended_at) {
                            $companies[$company->company_id]['hours'] += $durationByLine;
                        } else if ($slot->started_at >= $el->started_at && $slot->ended_at >= $el->ended_at) {
                            $companies[$company->company_id]['hours'] += abs(Carbon::parse($slot->started_at)->diffInHours(Carbon::parse($el->ended_at)));
                        } else if ($slot->started_at <= $el->started_at && $slot->ended_at < $el->ended_at) {
                            $companies[$company->company_id]['hours'] += abs(Carbon::parse($slot->ended_at)->diffInHours(Carbon::parse($el->started_at)));
                        }
                    });
                /*foreach(explode(',', $el->workers) as $id) {
                    if ($id == '' || $id == null) {
                        continue;
                    }
                    $worker = Workers::find($id);
                    $company = Companies::where('company_id', $worker->company_id)->first();

                    if (!isset($companies[$company->company_id])) {
                        $companies[$company->company_id] = [
                            'title' => $company->title,
                            'hours' => 0
                        ];
                    }

                    $companies[$company->company_id]['hours'] += $durationByLine;
                }*/
                //return $el;
            });

        array_push($columns, [], ['КОМПАНИИ']);
        foreach ($companies as $company) {
            $columns[] = [
                $company['title'],
                $company['hours']
            ];
        }



        // foreach ($data as $col) {
        //     $grouped[$col['line_id']][] = $col;
        // }
        // $companies = [];

        // foreach ($grouped as $line_id => &$f) {
        //     $i = 0;
        //     if (!isset($companies[$line_id]))
        //         $companies[$line_id] = [];

        //     // обходим журнал по порядку
        //     while ($i < count($f)) {
        //         if ($f[$i]['type'] == 3) {
        //             // Перестановка начального времени
        //             preg_match('/\d{2}:\d{2}:\d{2}/', $f[$i]['extra'], $matches);
        //             $oldTime = $matches[0];
        //             if ($oldTime) {
        //                 $newTime = Carbon::parse($f[$i]['created_at']);
        //                 $diff = abs($newTime->diffInHours(Carbon::createFromFormat('H:i:s', $oldTime)));

        //                 $comps = Workers::whereIn('worker_id', explode(',', $f[$i]['workers']))->select('company_id')->distinct()->get();
        //                 if (count($comps) > 0) {
        //                     $buf = [];
        //                     foreach ($comps as $comp) {
        //                         if (!isset($buf[$line_id]))
        //                             $buf[$line_id] = [];
        //                         if (isset($buf[$line_id][$comp->company_id])) {
        //                             $buf[$line_id][$comp->company_id] += 1;
        //                         } else {
        //                             $buf[$line_id][$comp->company_id] = 1;
        //                         }
        //                     }
        //                     if (isset($buf[$line_id])) {
        //                         $buf[$line_id] = array_map(function ($val) use ($diff) {
        //                             return $val * $diff;
        //                         }, $buf[$line_id]);
        //                         $companies[$line_id][] = $buf[$line_id];
        //                     }
        //                 }
        //             }
        //             $i += 1;
        //         } else if ($f[$i]['type'] == 1 and $f[$i + 1]['type'] == 2) {
        //             $newTime = Carbon::parse($f[$i]['created_at']);
        //             $oldTime = Carbon::parse($f[$i + 1]['created_at']);

        //             $diff = abs($newTime->diffInHours($oldTime));

        //             $comps = Workers::whereIn('worker_id', explode(',', $f[$i]['workers']))->get('company');
        //             if (count($comps) > 0) {
        //                 $buf = [];
        //                 foreach ($comps as $comp) {
        //                     if (!isset($buf[$line_id])) {
        //                         $buf[$line_id] = [];
        //                     }
        //                     if (isset($buf[$line_id][$comp->company_id])) {
        //                         $buf[$line_id][$comp->company_id] += 1;
        //                     } else {
        //                         $buf[$line_id][$comp->company_id] = 1;
        //                     }
        //                 }
        //                 $buf[$line_id] = array_map(function ($val) use ($diff) {
        //                     return $val * $diff;
        //                 }, $buf[$line_id]);
        //                 $companies[$line_id][] = $buf[$line_id];
        //             }
        //             $i += 2;
        //         }
        //     }
        // }
        // foreach ($companies as $line => $v) {
        //     $columns[] = [Lines::find($line)->first()->title];

        //     $arr = [];
        //     foreach ($v as $item) {
        //         foreach ($item as $key => $value) {
        //             if (isset($arr[$key])) {
        //                 $arr[$key] += $value;
        //             } else {
        //                 $arr[$key] = $value;
        //             }
        //         }
        //     }
        //     foreach ($arr as $key => $value) {
        //         $columns[] = [$key, $value];
        //     }
        // }

        $session = Util::getSessionAsArray($request);
        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = "Простои_$session[date]_" . ($session['isDay'] ? 'День' : 'Ночь') . ".xlsx";
        $xlsx->downloadAs($name);
        // return $name;
    }
}
