<?php

namespace App\Http\Controllers;

use App\Models\Workers;
use Illuminate\Http\Request;

class WorkersController extends Controller
{
    public function getList() {
        return Workers::all()->toJson();
    }

    static public function add($company = null, $title = null, $b_start = null, $b_end = null)  {
        if (empty($title)) return;

        $worker = new Workers();

        $worker->company = $company;
        $worker->title = $title;
        $worker->break_started_at = $b_start;
        $worker->break_ended_at = $b_end;

        $worker->save();
        return $worker->id;
    }

    static public function save(Request $request) {
        $data = $request->post();
        if (empty($data)) return 0;
        foreach ($data as $r) {
            $slot = Workers::find($r['worker_id']);
            $slot->break_started_at = $r['started_at'];
            $slot->break_ended_at = $r['ended_at'];
            $slot->save();
            if (!$slot)
                echo 'Ошибка.';
        }
        return 0;
    }

    static public function dropData() {
        return Workers::truncate();
    }
}
