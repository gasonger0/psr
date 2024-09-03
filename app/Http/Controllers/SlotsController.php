<?php

namespace App\Http\Controllers;

use App\Models\Slots;
use Illuminate\Http\Request;

class SlotsController extends Controller
{
    public function getList() {
        return Slots::all()->toJson();
    }

    static public function add($line_id = null, $worker_id = null,$start = null, $end = null) {
        if (!$line_id) return;
        $slot = new Slots();
        

        $slot->line_id = $line_id;
        $slot->started_at = $start;
        $slot->ended_at = $end;
        $slot->worker_id = $worker_id;

        $slot->save();

        return $slot->slot_id;

    }

    static public function dropData() {
        return Slots::truncate();
    }
}
