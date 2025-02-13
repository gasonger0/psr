<?php

namespace App\Http\Controllers;

use App\Models\LinesExtra;
use Illuminate\Http\Request;
use App\Http\Controllers\SlotsController;
use Illuminate\Support\Facades\Session;

class LinesExtraController extends Controller
{
    public static function get($line_id)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        return LinesExtra::where('line_id', $line_id)->where('date', $date)->first();
    }

    public static function add($line_id, $fields = [])
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $extra = new LinesExtra();
        $extra->line_id = $line_id;
        $extra->date = $date;
        if ($fields) {
            if ($fields['time']) {
                $time = explode('-', $fields['time']);
                $extra->started_at = $time[0];
                $extra->ended_at = $time[1];
            }
            $extra->prep_time = $fields['prep'] ?? null;
            $extra->workers_count = $fields['people'] ?? null;
            $extra->after_time = $fields['end'] ?? null;
            $extra->master = $fields['master'] ?? null;
            $extra->engineer = $fields['engineer'] ?? null;
            $extra->has_detector = $fields['has_detector'] ?? false;
            $extra->detector_start = $fields['detector_start'] ?? null;
            $extra->detector_end = $fields['detector_end'] ?? null;
            $extra->extra_title = $fields['extra_title'] ?? null;
        }
        $extra->save();
        return $extra;
    }

    public static function update($line_id, $fields = null)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $extra = LinesExtra::where('line_id', $line_id)->where('date', $date)->first();
        $oldEnd = $extra->ended_at;
        $oldStart = $extra->started_at;
        $extra->workers_count = $fields['workers_count'] ?? $extra->workers_count;
        $extra->prep_time = $fields['prep_time'] ?? $extra->prep_time;
        $extra->after_time = $fields['after_time'] ?? $extra->after_time;
        $extra->extra_title = $fields['extra_title'] ?? $extra->extra_title;
        if ($fields['started_at']) {
            $extra->started_at = strval($fields['started_at']);
            $extra->ended_at = strval($fields['ended_at']);
            $extra->cancel_reason = $fields['cancel_reason'] ?? $extra->cancel_reason;
        }
        $extra->master = $fields['master'] ?? $extra->master;
        $extra->engineer = $fields['engineer'] ?? $extra->engineer;
        $extra->has_detector = $fields['has_detector'] ?? $extra->has_detector;
        $extra->detector_start = $fields['detector_start'] ?? $extra->detector_start;
        $extra->detector_end = $fields['detector_end'] ?? $extra->detector_end;
        $extra->save();
        return [
            'start' => $oldStart,
            'end' => $oldEnd
        ];
    }

    static public function down(Request $request)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $line = LinesExtra::where('line_id', $request->post('id'))->where('date', $date)->first();
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
}
