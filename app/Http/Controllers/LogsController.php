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

class LogsController extends Controller
{
    public static function create(array $data)
    {
        $workers = Slots::withSession($data)
            ->where('line_id', $data['line_id'])
            ->where('started_at', '<=', Carbon::now()->format('Y-m-d H:i:s'))
            ->where('ended_at', '>=', Carbon::now()->format('Y-m-d H:i:s'))
            ->map(function ($i) {
                return $i->worker_id;
            });

        $log = Logs::create($data + [
            'people_count' => count($workers),
            'workers' => implode(',', $workers),
            'started_at' => Carbon::now()->format('Y-m-d H:i:s')

        ]);
        return Util::successMsg($log, 201);
    }

    public static function update(array $data)
    {
        $log = Logs::withSession($data)->where('line_id', $data['line_id'])->where('ended_at', null)->first();
        if ($log) {
            $log->update([
                'ended_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }
        return Util::successMsg('Простой завершён');
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
        Logs::withSession($request)->get()->orderBy('created_at', 'ASC')->each(function ($el) use (&$columns, &$companies) {
            $columns[] = [
                $el->log_id,
                $el->line,
                Carbon::parse($el->started_at)->format('H:i:s'),
                Carbon::parse($el->ended_at)->format('H:i:s'),
                $el->people_count
            ];

            $duration = Carbon::parse($el->started_at)->diffInHours(Carbon::parse($el->ended_at));

            foreach(explode(',', $el->workers) as $id) {
                $worker = Workers::find($id);
                $company = Companies::where('company_id', $worker->company_id)->first();

                if (!isset($companies[$company->company_id])) {
                    $companies[$company->company_id] = [
                        'title' => $company->title,
                        'hours' => 0
                    ];
                }

                $companies[$company->company_id]['hours'] += $duration;
            }
            return $el;
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
        $name = "Простои_$session[date]_". ($session['isDay'] ? 'День' : 'Ночь') .".xlsx";
        $xlsx->downloadAs($name);
        // return $name;
    }
}
