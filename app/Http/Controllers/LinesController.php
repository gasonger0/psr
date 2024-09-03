<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use Illuminate\Http\Request;

class LinesController extends Controller
{
    static public function getList($columns = ['*']) {
        return Lines::all($columns)->toJson();
    }

    static public function add($title = null, $workers_count = null, $started_at = null, $ended_at = null) {
        if (empty($title)) return;

        $line = new Lines;
        
        $line->title            = $title;
        $line->workers_count    = $workers_count;
        $line->started_at       = $started_at;
        $line->ended_at         = $ended_at;

        $line->save();
        return $line->line_id;
    }

    static public function dropData() {
        return Lines::truncate();
    }
}
