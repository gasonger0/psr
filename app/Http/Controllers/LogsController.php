<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Logs;
use Carbon\Carbon;
use Shuchkin\SimpleXLSXGen;

class LogsController extends Controller
{
    static public function add(Request $request){
        if (empty($request->post())) return -1;
        $log = new Logs();

        $log->action = $request->post('action');
        if ($extra = $request->post('extra')) {
            $log->extra = $extra;
        }
        $log->save();
        return;
    }

    static public function getAll(){
        return Logs::orderBy('created_at', 'DESC')->get()->toJson();
    }

    static public function logXlsx(){
        $columns = [[
            'ИД',
            'Создан',
            'Действие',
            'Описание',
            'Кол-во человек на линии'   
        ]];


        $data = self::getAll();
        $data = json_decode($data, true,512, JSON_OBJECT_AS_ARRAY);
        foreach($data as $col) {
            unset($col['updated_at']);
            $col['created_at'] = Carbon::parse($col['created_at'])->format('d.m.Y H:i:s');
            $columns[] = array_values($col);
        }
        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Простои_'.date('d_m_Y-H:i:s', time()) . '.xlsx';
        $xlsx->saveAs($name);
        return $name;
    }
}
