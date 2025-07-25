<?php

namespace App\Http\Controllers;

use App\Models\LinesExtra;
use Illuminate\Http\Request;
use App\Http\Controllers\SlotsController;

class LinesExtraController extends Controller
{
    public static function get($date,$isDay, $line_id)
    {
        return LinesExtra::where('line_id', $line_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->first();
    }

    public static function add($date, $isDay, $line_id, $fields = [])
    {
        $extra = new LinesExtra();
        $extra->line_id = $line_id;
        $extra->date = $date;
        $extra->isDay = $isDay;
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

    public static function update($date, $isDay, $line_id, $fields = null)
    {
        $extra = LinesExtra::where('line_id', $line_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->first();
        $oldEnd = $extra->ended_at;
        $oldStart = $extra->started_at;
        $extra->workers_count = $fields['workers_count'] ?? $extra->workers_count;
        $extra->prep_time = $fields['prep_time'] ?? $extra->prep_time;
        $extra->after_time = $fields['after_time'] ?? $extra->after_time;
        $extra->extra_title = $fields['extra_title'] ?? $extra->extra_title;
        if ($fields['started_at']) {
            var_dump('Updated line time');
            $extra->started_at = (new \DateTime($fields['started_at']))->sub(new \DateInterval('PT' .( $extra->prep_time ?? 0) . 'M'));
            $extra->ended_at = (new \DateTime($fields['ended_at']))->add(new \DateInterval('PT' . ($extra->after_time ?? 0) . 'M'));
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
        $date = $request->cookie('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        $line = LinesExtra::where('line_id', $request->post('id'))
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->first();
        $downFrom = $line->down_from;
        if ($downFrom != null) {
            $diff = (new \DateTime($downFrom))->diff(new \DateTime());
            $line->down_time = $line->down_time + $diff->h * 60 + $diff->i;
            $line->down_from = null;
            $line->save();
            SlotsController::down($request->cookie('date'), filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN), $line->line_id, $downFrom);
        } else {
            $line->down_from = now('Europe/Moscow');
            $line->save();
        }
    }
}
