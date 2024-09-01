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
        if (!$title) return;

        $worker = new Workers();

        $worker->company = $company;
        $worker->title = $title;
        $worker->break_started_at = $b_start;
        $worker->break_ended_at = $b_end;

        $worker->save();

        return;
    }
}
