<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\Slots;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LinesController extends Controller
{
    static public function getList()
    {   
        $result = [];
        Lines::all()->each(function ($line) use(&$result)  {
            $line_extra = LinesExtraController::get($line->line_id);
            if (!$line_extra) {
                $line_extra = LinesExtraController::add($line->line_id, self::getDefaults($line->line_id));
            }
            $result[] = array_merge($line->toArray(), $line_extra->toArray());
        });
            
        return json_encode($result);
    }

    static public function add($array)
    {
        if (empty($array['title']))
            return;

        $line = new Lines;
        $line->title = $array['title'];
        $line->color = $array['color'];

        $line->type_id = $array['type_id'];
        $line->save();
        LinesExtraController::add($line->line_id, 
            array_merge($array, self::getDefaults($line->line_id))
        );
        return $line->line_id;
    }

    static public function save(Request $request)
    {
        if ($request->post('line_id') == -1) {
            $id = self::add($request->post());
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
            $d = LinesExtraController::update($line->line_id, $request->post());
            $start = $d['start'];
            $end = $d['end'];
            $line->color = $request->post('color');
            $line->type_id = $request->post('type_id');
            $line->title = $request->post('title');
            $line->save();

            if ($request->post('started_at')) {
                SlotsController::afterLineUpdate(
                    $request->post('line_id'),
                    $request->post('started_at'),
                    $start,
                    $request->post('ended_at'),
                    $end
                );
                ProductsPlanController::afterLineUpdate($request->post('line_id'), $request->post('started_at'), $start, $request->post('ended_at'), $end);
                if ($line->cancel_reason != null) {
                    $d = Slots::where('line_id', '=', $line->line_id)->where('started_at', '<=', $start)->get('worker_id')->toArray();
                    $d = array_map(function ($a) {
                        return $a['worker_id'];
                    }, $d);
                    $request = new Request([], [
                        'line_id' => $line->line_id,
                        'action' => 'Перенос времени работы линии',
                        'extra' => 'Причина: ' . $request->post('cancel_reason_extra') . PHP_EOL . 'Старое время: ' . $start,
                        'people_count' => $request->post('workers_count'),
                        'type' => 3,
                        'workers' => implode(',', $d),
                    ]);
                    LogsController::add($request);
                }
            }
            return json_encode([
                "success" => true
            ]);
        }
    }

    static public function clear()
    {
        Lines::truncate();
    }

    public static function getDefaults($line_id = false)
    {
        $defs =  [
            [
                'line_id' => 33,
                'title' => 'СТАРАЯ вертикальная установка',
                'time' => null,
                'people' => 3,
                'prep' => 0,
                'end' => 10
            ],
            [
                'line_id' => 34,
                'title' => 'Резка суфле-уп.',
                'time' => null,
                'people' => 2,
                'prep' => 10,
                'end' => 10
            ],
            [
                'line_id' => 40,
                'title' => 'Сборка подарочного набора',
                'time' => null,
                'people' => 1,
                'prep' => 0,
                'end' => 0 
            ],
            [
                'line_id' => 47,
                'title' => 'Варочный участок',
                'time' => null,
                'people' => 1,
                'prep' => 0,
                'end' => 0
            ],
            // 34, 40, 47
            [
                'line_id' => 44,
                'title' => 'ВАРКА СУФЛЕ',
                'time' => '7:00-9:00',
                'people' => 5,
                'prep' => 60,
                'end' => 30
            ],
            [
                'line_id' => 45,
                'title' => 'Резка суфле',
                'time' => '7:00-10:00',
                'people' => 2,
                'prep' => 10,
                'end' => 10
            ],
            [
                'line_id' => 6,
                'title' => 'Машина для производства стаканчиков',
                'time' => '9:00-12:00',
                'people' => 5,
                'prep' => 30,
                'end' => 30
            ],
            [
                'line_id' => 7,
                'title' => 'Машина для формовки Dream Kissм (ОКА)',
                'time' => '9:00-12:00',
                'people' => 4,
                'prep' => 30,
                'end' => 30
            ],
            [
                'line_id' => 8,
                'title' => 'Первая фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'line_id' => 9,
                'title' => 'Вторая фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'line_id' => 10,
                'title' => 'Третья фис машина',
                'time' => '8:00-18:00',
                'people' => 4,
                'prep' => 120,
                'end' => 60
            ],
            [
                'line_id' => 11,
                'title' => 'Непрерывная линия №1',
                'time' => '8:00-18:30',
                'people' => 3,
                'prep' => 120,
                'end' => 60
            ],
            [
                'line_id' => 12,
                'title' => 'НЕПРЕРЫВНАЯ ЛИНИЯ №2',
                'time' => '8:00-18:30',
                'people' => 3,
                'prep' => 120,
                'end' => 60
            ],
            [
                'line_id' => 43,
                'title' => 'ОБСЫПКА КОКОСОВОЙ СТРУЖКОЙ',
                'time' => '8:00-9:00',
                'people' => 3,
                'prep' => null,
                'end' => 60
            ],
            [
                'line_id' => 41,
                'title' => 'Непрерывная линия №2 – сахарная пудра',
                'time' => '8:00-20:00',
                'people' => 12,
                'prep' => 20,
                'end' => 30
            ],
            [
                'line_id' => 13,
                'title' => 'FLOY PAK 7 с апликатором',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 14,
                'title' => 'НЕПРЕРЫВНАЯ ЛИНИЯ №2 - Шоколадная линия',
                'time' => '8:00-20:00',
                'people' => 6,
                'prep' => 20,
                'end' => 30
            ],
            [
                'line_id' => 15,
                'title' => 'FLOY PAK 9',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 16,
                'title' => 'FLOY PAK Большой китайский №4',
                'time' => '8:00-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 17,
                'title' => 'Непрерывная линия сахарной пудры №1 - Шоколадная линия',
                'time' => '8:00-20:00',
                'people' => 6,
                'prep' => 20,
                'end' => 30
            ],
            [
                'line_id' => 18,
                'title' => 'Непрерывная линия сахарной пудры №1',
                'time' => '8:00-20:00',
                'people' => 12,
                'prep' => 20,
                'end' => 30
            ],
            [
                'line_id' => 19,
                'title' => 'FLOY PAK 6 с апликатором',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 20,
                'title' => 'Шоколадная линия 1',
                'time' => '9:30-20:00',
                'people' => 7,
                'prep' => 5,
                'end' => 30
            ],
            [
                'line_id' => 21,
                'title' => 'FLOY PAK 8',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 22,
                'title' => 'FLOY PAK 3',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 23,
                'title' => 'FLOY PAK 1',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 24,
                'title' => 'Линия эквивалент',
                'time' => '9:30-20:00',
                'people' => 5,
                'prep' => 5,
                'end' => 30
            ],
            [
                'line_id' => 25,
                'title' => 'Полуавтоматическая линия сахарной пудры',
                'time' => '9:30-20:00',
                'people' => 12,
                'prep' => 5,
                'end' => 15
            ],
            [
                'line_id' => 26,
                'title' => 'FLOY PAK 2',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 27,
                'title' => 'FLOY PAK №10',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 28,
                'title' => 'FLOY PAK №5',
                'time' => '9:30-20:00',
                'people' => 4,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 29,
                'title' => 'Термоупаковка 1',
                'time' => '9:30-20:00',
                'people' => 2,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 30,
                'title' => 'Термоупаковка 2',
                'time' => '9:30-20:00',
                'people' => 2,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 31,
                'title' => 'Линия ONE SHOT',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => 30,
                'end' => 30
            ],
            [
                'line_id' => 32,
                'title' => 'НОVAЯ вертикальная установка',
                'time' => '8:00-20:00',
                'people' => 3,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 42,
                'title' => 'ДАТИРОВАНИЕ',
                'time' => '8:00-20:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 46,
                'title' => 'Участок ОГН',
                'time' => '8:00-20:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 36,
                'title' => 'Картонажный участок',
                'time' => '8:00-9:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ],
            [
                'line_id' => 37,
                'title' => 'Сборка ящиков под продукцию',
                'time' => '8:00-10:00',
                'people' => 1,
                'prep' => null,
                'end' => 10
            ]
        ];
        if ($line_id) {
            $index = array_search($line_id, array_column($defs, 'line_id'));
            if ($index !== false) {
                return $defs[$index];
            } else {
                return false;
            }
        }
    }

    public function delete(Request $request) {
        $line_id = $request->post('line_id');
        Lines::where('line_id', $line_id)->delete();
        LinesExtra::where('line_id', $line_id)->delete();
        return true;
    }
}
