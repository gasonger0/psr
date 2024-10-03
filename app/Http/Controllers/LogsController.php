<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Logs;
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
}
