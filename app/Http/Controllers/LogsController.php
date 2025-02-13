<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Logs;
use Carbon\Carbon;
use Shuchkin\SimpleXLSXGen;
use App\Models\Lines;
use App\Models\Workers;

class LogsController extends Controller
{
    static public function add(Request $request)
    {
        if (empty($request->post())) return -1;
        $log = new Logs();

        $log->action = $request->post('action');
        if ($extra = $request->post('extra')) {
            $log->extra = $extra;
        }
        $log->line_id = $request->post('line_id');
        $log->people_count = $request->post('people_count');
        $log->created_at = now('Europe/Moscow')->format(format: 'H:i:s');
        $log->workers = $request->post('workers') ?? '';
        $log->type = $request->post('type');
        $log->save();
        return;
    }

    static public function getAll()
    {
        $logs = Logs::orderBy('created_at', 'DESC')->get()->toArray();
        foreach ($logs as &$log) {
            $log['line'] = Lines::find($log['line_id'])->title;
        }
        return json_encode($logs);
    }

    static public function logXlsx()
    {
        $data = self::getAll();
        $data = json_decode($data, true, 512, JSON_OBJECT_AS_ARRAY);
        $columns = [[
            'ИД',
            'Линия',
            'Создан',
            'Действие',
            'Описание',
            'Кол-во человек на линии'
        ]];
        foreach ($data as $col) {
            unset($col['updated_at']);
            $col['created_at'] = Carbon::parse($col['created_at'])->format('H:i:s');
            $columns[] = [
                $col['log_id'],
                $col['line'],
                $col['created_at'],
                $col['action'],
                $col['extra'],
                $col['people_count']
            ];
        }
        $columns[] = [];
        $columns[] = ['КОМПАНИИ'];

        $grouped = [];
        foreach ($data as $col) {
            $grouped[$col['line_id']][] = $col;
        }
        $companies = [];

        foreach ($grouped as $line_id => &$f) {
            $sorter = array_column($f, 'created_at');
            array_multisort($sorter, SORT_ASC, $f);
            $i = 0;
            if (!isset($companies[$line_id])) $companies[$line_id] = [];
            while ($i < count($f)) {
                if ($f[$i]['type'] == 3) {
                    // Перестановка начального времени
                    preg_match('/\d{2}:\d{2}:\d{2}/', $f[$i]['extra'], $matches);
                    $oldTime = $matches[0];
                    if ($oldTime) {
                        $newTime = Carbon::parse($f[$i]['created_at']);
                        $diff = abs($newTime->diffInHours(Carbon::createFromFormat('H:i:s', $oldTime)));

                        $comps = Workers::whereIn('worker_id', explode(',', $f[$i]['workers']))->get(['worker_id','company']);
                        if ($comps) {
                            $buf = [];
                            foreach ($comps as $comp) {
                                if (!isset($buf[$line_id])) $buf[$line_id] = [];
                                if (isset($buf[$line_id][$comp->company])) {
                                    $buf[$line_id][$comp->company] += 1;
                                } else {
                                    $buf[$line_id][$comp->company] = 1;
                                }
                            }
                            if (isset($buf[$line_id])) {
                                $buf[$line_id] = array_map(function ($val) use ($diff) {
                                    return $val * $diff;
                               }, $buf[$line_id]);
                                $companies[$line_id][] = $buf[$line_id];
                            }
                        }
                    }
                    $i += 1;
                } else if ($f[$i]['type'] == 1 and $f[$i + 1]['type'] == 2) {
                    $newTime = Carbon::parse($f[$i]['created_at']);
                    $oldTime = Carbon::parse($f[$i + 1]['created_at']);

                    $diff = abs($newTime->diffInHours($oldTime));

                    $comps = Workers::whereIn('worker_id', explode(',',$f[$i]['workers']))->get('company');
                    if($comps){
                        $buf = [];
                        foreach ($comps as $comp) {
                            if (!isset($buf[$line_id])) $buf[$line_id] = [];
                            if (isset($buf[$line_id][$comp->company])) {
                                $buf[$line_id][$comp->company] += 1;
                            } else {
                                $buf[$line_id][$comp->company] = 1;
                            }
                        }
                        $buf[$line_id] = array_map(function ($val) use ($diff) {
                            return $val * $diff;
                        }, $buf[$line_id]);
                        $companies[$line_id][] = $buf[$line_id];
                    }
                    $i += 2;
                }
            }
        }
        foreach ($companies as $line => $v) {
            $columns[] = [Lines::find($line)->first()->title];

            $arr = [];
            foreach ($v as $item) {
                foreach ($item as $key => $value) {
                    if (isset($arr[$key])) {
                        $arr[$key] += $value;
                    } else {
                        $arr[$key] = $value;
                    }
                }
            }
            foreach ($arr as $key => $value) {
                $columns[] = [$key, $value];
            }
        }
        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Простои_' . date('d_m_Y-H:i:s', time()) . '.xlsx';
        $xlsx->downloadAs($name);
        // return $name;
    }
    static public function clear()
    {
        return Logs::truncate();
    }
}
