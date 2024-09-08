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

    static public function save(Request $request){
        if ($request->post('line_id') == -1) {
            $id = self::add(
                $request->post('title'),
                $request->post('workers_count'),
                $request->post('started_at'),
                $request->post('ended_at')
            );
            if ($id) {
                return json_encode([
                    "success" => true,
                    'msg' => 'Линия № ' . $id . ' успешно добавлена'
                ]);
            } else {
                return json_encode([
                    "success" => 'false'
                ]);
            }
        }

        $line = Lines::find($request->post('line_id'));

        if ($line) {
            $oldStart = strVal($line->started_at);
            $line->workers_count = $request->post('workers_count');
            $line->started_at = strval($request->post('started_at'));
            $line->ended_at = strval($request->post('ended_at'));

            $line->save();

            if ($request->post('started_at')) {
                $time = new \DateTime($oldStart);
                $tmie2 = new \DateTime($request->post('started_at'));
                $diff = $time->diff($tmie2);
                $total = $diff->i + $diff->h * 60;
                SlotsController::afterLineUpdate($request->post('line_id'), $total);
                ProductsController::afterLineUpdate($request->post('line_id'), $total);
            }
            return json_encode([
                "success" => true
            ]);
        }
    }

    static public function dropData() {
        return Lines::truncate();
    }
}
