<?php

namespace App\Http\Controllers;

use App\Models\ProductsPlan;
use Illuminate\Http\Request;
use App\Models\Logs;
use Carbon\Carbon;
use Shuchkin\SimpleXLSXGen;
use App\Models\Lines;

class LogsController extends Controller
{
    static public function add(Request $request){
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
        $log->save();
        return;
    }

    static public function getAll(){
        $logs = Logs::orderBy('created_at', 'DESC')->get()->toArray();
        foreach ($logs as &$log) {
            $log['line'] = Lines::find($log['line_id'])->title;
            unset($log['line_id']);
        }
        return json_encode($logs);  
    }

    static public function logXlsx(){
        $data = self::getAll();
        $data = json_decode($data, true,512, JSON_OBJECT_AS_ARRAY);
        $columns = [[
            'ИД',
            'Линия',
            'Создан',
            'Действие',
            'Описание',
            'Кол-во человек на линии'   
        ]];
        foreach($data as $col) {
            unset($col['updated_at']);
            $col['created_at'] = Carbon::parse($col['created_at'])->format('d.m.Y H:i:s');
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
        // while (!empty($data)) {
        //    Я хуй знает, как это делать блять 
        // }
        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Простои_'.date('d_m_Y-H:i:s', time()) . '.xlsx';
        $xlsx->downloadAs($name);
        // return $name;
    }
    static public function clear(){
        Logs::truncate();
        return;
    }
}
