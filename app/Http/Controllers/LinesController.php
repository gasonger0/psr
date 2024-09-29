<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LinesController extends Controller
{
    static public function getList($columns = ['*'])
    {
        return json_encode(DB::select(
            'SELECT l.*, (SELECT shift FROM products p WHERE p.line_id = l.line_id LIMIT 1) AS shift FROM `lines` l;'
        ));
    }

    static public function add($title = null, $workers_count = null, $started_at = null, $ended_at = null, $color = null)
    {
        if (empty($title))
            return;

        $line = new Lines;

        $line->title = $title;
        $line->workers_count = $workers_count;
        $line->started_at = $started_at;
        $line->ended_at = $ended_at;
        $line->color = $color;

        $line->save();
        return $line->line_id;
    }

    static public function save(Request $request)
    {
        if ($request->post('line_id') == -1) {
            $id = self::add(
                $request->post('title'),
                $request->post('workers_count'),
                $request->post('started_at'),
                $request->post('ended_at'),
                $request->post('color')
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
            $time = new \DateTime($oldStart);
            $tmie2 = new \DateTime($request->post('started_at'));
            $diff = $time->diff($tmie2);

            $line->workers_count = $request->post('workers_count');
            if ($request->post('started_at')) {
                $line->started_at = strval($request->post('started_at'));
                $line->cancel_reason = $request->post('cancel_reason');
            }
            // $line->ended_at = strval($request->post('ended_at'));
            $line->color = $request->post('color');

            $line->save();
            $line->shiftEnd($diff->i + $diff->h * 60);

            if ($request->post('started_at')) {
                $total = $diff->i + $diff->h * 60;
                SlotsController::afterLineUpdate($request->post('line_id'), $total);
                ProductsController::afterLineUpdate($request->post('line_id'), $total);
            }
            return json_encode([
                "success" => true
            ]);
        }
    }

    static public function down(Request $request)
    {
        $line = Lines::find($request->post('id'));
        $downFrom = $line->down_from;
        if ($downFrom != null) {
            $diff = (new \DateTime($downFrom))->diff(new \DateTime());
            $line->down_time = $line->down_time + $diff->h * 60 + $diff->i;
            $line->down_from = null;
            $line->save();
            SlotsController::down($line->line_id, $downFrom);
        } else {
            $line->down_from = now('Europe/Moscow');
            $line->save();
        }
    }

    static function calcTimeShift($from, $to) {
        
    }
    static public function dropData()
    {
        return Lines::truncate();
    }
}
