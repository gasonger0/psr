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
        return Lines::all()->toJson();
    }

    static public function add($title = null, $workers_count = null, $started_at = null, $ended_at = null, $color = null, $master = null, $engineer = null, $type_id = null, $prep_time = null, $after_time = null)
    {
        if (empty($title))
            return;

        $line = new Lines;

        $line->title = $title;
        $line->workers_count = $workers_count;
        $line->started_at = $started_at;
        $line->ended_at = $ended_at;
        $line->color = $color;
        $line->master = $master;
        $line->engineer = $engineer;
        $line->type_id = $type_id;
        $line->prep_time = $prep_time;
        $line->after_time = $after_time;
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
                $request->post('color'),
                $request->post('master'),
                $request->post('engineer'),
                $request->post('type_id'),
                $request->post('prep_time'),
                $request->post('after_time')
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
            // $time = new \DateTime($oldStart);
            // $tmie2 = new \DateTime($request->post('started_at'));
            // $diff = $time->diff($tmie2);

            $start = $line->started_at;
            $end = $line->ended_at;
            $line->workers_count = $request->post('workers_count');
            $line->prep_time = $request->post('prep_time');
            $line->after_time = $request->post('after_time');
            if ($request->post('started_at')) {
                $line->started_at = strval($request->post('started_at'));
                $line->ended_at = strval($request->post('ended_at'));
                $line->cancel_reason = $request->post('cancel_reason');
            }
            // $line->ended_at = strval($request->post('ended_at'));
            $line->color = $request->post('color');
            $line->master = $request->post('master');
            $line->engineer = $request->post('engineer');
            $line->type_id = $request->post('type_id');
            $line->title = $request->post('title');
            $line->save();
            // $line->shiftEnd($diff->i + $diff->h * 60);

            if ($request->post('started_at')) {
                // $total = $diff->i + $diff->h * 60;
                $start = $line->started_at;
                SlotsController::afterLineUpdate(
                    $request->post('line_id'),
                    $request->post('started_at'),
                    $start,
                    $request->post('ended_at'),
                    $end
                );
                // ProductsController::afterLineUpdate($request->post('line_id'), $request->post('started_at'), $start, $request->post('ended_at'), $end);
                ProductsPlanController::afterLineUpdate($request->post('line_id'), $request->post('started_at'), $start, $request->post('ended_at'), $end);
                if ($line->cancel_reason != null) {
                    $request = new Request([], [
                        'line_id' => $line->line_id,
                        'action' => 'Перенос времени работы линии',
                        'extra' => 'Причина: ' . $request->post('cancel_reason_extra'),
                        'people_count' => $line->workers_count
                    ]);
                    LogsController::add($request);
                }
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

    static public function dropData()
    {
        Lines::truncate();
    }

    public static function getDefaults()
    {
        return [
            [
                'title' => 'ВАРКА СУФЛЕ',
                'time' => '7:00-9:00',
                'people' => 5,
                'prep' => 60,
                'end' => 30
            ],
            [
                'title' => 'Резка суфле',
                'time' => '7:00–10:00',
                'people' => 2,
                'prep' => 10,
                'end' => 10
            ],
            [
                'title' => 'Машина для производства стаканчиков',
                'time' => '9:00-12:00',
                'people' => 5,
                'prep' => 30,
                'end' => 30
            ],
            [
                'title' => 'Машина для формовки Dream Kissм  (ОКА)',
                'time' => '9:00-12:00',
                'people' => 4,
                'prep' => 30,
                'end' => 30
            ],
            [
                'title' => 'Первая фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'title' => 'Вторая фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'title' => 'Третья фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'title' => 'Непрерывная линия №1',
                'time' => '8:00-18:30',
                'people' => 3,
                'prep' => 120,
                'end' => 60
            ],
            [
                'title' => 'НЕПРЕРЫВНАЯ ЛИНИЯ №2',
                'time' => '8:00-18:30',
                'people' => 3,
                'prep' => 120,
                'end' => 60
            ],
            [
                'title' => 'ОБСЫПКА КОКОСОВОЙ СТРУЖКОЙ',
                'time' => '8:00-9:00',
                'people' => 3,
                'prep' => null,
                'end' => 60
            ],
            [
                'title' => 'Непрерывная линия №2 – сахарная пудра',
                'time' => '8:00-20:00',
                'people' => 12,
                'prep' => 20,
                'end' => 30
            ],
            [
                'title' => 'FLOY PAK 7 с апликатором',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'НЕПРЕРЫВНАЯ ЛИНИЯ №2 - Шоколадная линия',
                'time' => '8:00-20:00',
                'people' => 6,
                'prep' => 20,
                'end' => 30
            ],
            [
                'title' => 'FLOY PAK 9',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'FLOY PAK Большой китайский №4',
                'time' => '8:00-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Непрерывная линия сахарной пудры №1 - Шоколадная линия',
                'time' => '8:00-20:00',
                'people' => 6,
                'prep' => 20,
                'end' => 30
            ],
            [
                'title' => 'Непрерывная линия сахарной пудры №1',
                'time' => '8:00-20:00',
                'people' => 12,
                'prep' => 20,
                'end' => 30
            ],
            [
                'title' => 'FLOY PAK 6 с апликатором',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Шоколадная линия 1',
                'time' => '9:30-20:00',
                'people' => 7,
                'prep' => 5,
                'end' => 30
            ],
            [
                'title' => 'FLOY PAK 8',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'FLOY PAK 3',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'FLOY PAK 1',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Линия эквивалент',
                'time' => '9:30-20:00',
                'people' => 5,
                'prep' => 5,
                'end' => 30
            ],
            [
                'title' => 'Полуавтоматическая линия сахарной пудры',
                'time' => '9:30-20:00',
                'people' => 12,
                'prep' => 5,
                'end' => 15
            ],
            [
                'title' => 'FLOY PAK 2',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'FLOY PAK №10',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'FLOY PAK №5',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Термоупаковка',
                'time' => '9:30-20:00',
                'people' => 2,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Линия ONE SHOT',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => 30,
                'end' => 30
            ],
            [
                'title' => 'НОVAЯ вертикальная установка',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'ДАТИРОВАНИЕ',
                'time' => '8:00-20:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Участок ОГН',
                'time' => '8:00-20:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Картонажный участок',
                'time' => '8:00-9:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'title' => 'Сборка ящиков под продукцию',
                'time' => '8:00-10:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ]
        ];
    }
}
