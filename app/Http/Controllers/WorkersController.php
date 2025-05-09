<?php

namespace App\Http\Controllers;

use App\Models\Workers;
use Illuminate\Http\Request;
use App\Models\Slots;
use DateTime;
use DateTimeZone;

class WorkersController extends Controller
{
    public function getList()
    {
        return Workers::all()->toJson();
    }

    static public function add($company = null, $title = null, $b_start = null, $b_end = null)
    {
        if (empty($title)) return;

        $worker = new Workers();

        $worker->company = $company;
        $worker->title = $title;
        $worker->break_started_at = $b_start;
        $worker->break_ended_at = $b_end;

        $worker->save();
        return $worker->worker_id;
    }

    static public function save(Request $request)
    {
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

    static public function change(Request $request)
    {
        foreach ($request->post() as $worker) {
            if ($worker['slot_id']) {
                $slot = Slots::find($worker['slot_id']);
                $slot->line_id = $worker['line_id'];
                $slot->save();
                // } else {
                //     SlotsController::add(

                //     )
            }
        };
    }

    static public function addFromWeb(Request $request)
    {
        $tz = new DateTimeZone('Europe/Moscow');
        $b_start = new DateTime($request->post('break')[0]);
        $b_start->setTimezone($tz);
        $b_end = new DateTime($request->post('break')[1]);
        $b_end->setTimezone($tz);
        return self::add(
            $request->post('company'),
            $request->post('title'),
            $b_start,
            $b_end
        );
    }

    function edit(Request $request)
    {
        $workers = [];
        foreach (Workers::all(['worker_id', 'title', 'company']) as $worker) {
            $workers[$worker->worker_id] = $worker;
        }
        $data = $request->post();
        foreach ($data as $worker) {
            if (isset($worker['worker_id'])) {
                // Edit
                $workers[$worker['worker_id']]['company'] = $worker['company'];
                $workers[$worker['worker_id']]['title'] = $worker['title'];
                $workers[$worker['worker_id']]->save();
                unset($workers[$worker['worker_id']]);
            } else {
                // New
                self::add($worker['company'], $worker['title']);
            }
        };

        if (!empty($workers)) {
            Workers::destroy(array_map(function ($i) {
                return $i->worker_id;
            }, $workers));
        }
    }

    static public function clear()
    {
        return Workers::truncate();
    }
}
