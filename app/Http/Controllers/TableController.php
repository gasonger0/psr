<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\ProductsCategories;
use App\Models\ProductsDictionary;
use App\Models\ProductsOrder;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\Responsible;
use App\Models\Slots;
use App\Models\Workers;
use App\Util;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class TableController extends Controller
{
    private $file = [];
    static $MCS = '<center><middle>';
    static $MCE = '</middle></center>';
    private static $skipPhrases = ['подготовительное время', 'заключительное время'];
    private static $colons = [
        0 => '', 
        '' => '',
        1 => 'Варочная колонка №1', 
        2 => 'Варочная колонка №2',
        3 => 'Варочные колонки №1 и №2'
    ];
    private static $hardware = [
        0 => 'Без оборудования', 
        "" => 'Без оборудования', 
        1 => 'Мондомикс', 
        2 => 'Торнадо', 
        3 => 'Китайский аэрос', 
        4 => 'Завёрточная машина №1', 
        5 => 'Завёрточная машина №2', 
        6 => 'Завёрточные машины №1, №2'
    ];
    
    private static function makeArrayHeader($session)
    {
        return
            [
                [
                    '',
                    '',
                    self::$MCS . '2' . self::$MCE,
                    self::$MCS . '3' . self::$MCE,
                    self::$MCS . '4' . self::$MCE,
                    self::$MCS . '5' . self::$MCE,
                    self::$MCS . '6' . self::$MCE,
                    self::$MCS . '7' . self::$MCE,
                    self::$MCS . '8' . self::$MCE,
                    self::$MCS . '9' . self::$MCE,
                    self::$MCS . '10' . self::$MCE,
                    self::$MCS . '11' . self::$MCE,
                    self::$MCS . '12' . self::$MCE,
                    self::$MCS . '13' . self::$MCE,
                    self::$MCS . '14' . self::$MCE,
                    self::$MCS . '15' . self::$MCE,
                    self::$MCS . '16' . self::$MCE,
                    self::$MCS . '17' . self::$MCE,
                    self::$MCS . '18' . self::$MCE,
                    self::$MCS . '19' . self::$MCE,
                    self::$MCS . '20' . self::$MCE,
                    self::$MCS . '21' . self::$MCE,
                    self::$MCS . '22' . self::$MCE,
                    self::$MCS . '23' . self::$MCE,
                    self::$MCS . '24' . self::$MCE,
                    self::$MCS . '25' . self::$MCE,
                    self::$MCS . '26' . self::$MCE,
                    self::$MCS . '27' . self::$MCE,
                    self::$MCS . '28' . self::$MCE
                ],
                [
                    '<style height="52">Дата</style>',
                    '<style height="52">' . $session['date'] . '</style>'
                ],
                [
                    '<style height="52">Смена:</style>',
                    $session['isDay'] ? 'День' : 'Ночь',
                    '',
                    'план:',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'факт:',
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . 'Ген.директор ООО КФ "Сокол"' . self::$MCE
                ],
                [
                    self::$MCS . '<b>№</b>' . self::$MCE,
                    '<style border="#000000" font-size="20">' . self::$MCS . '<b>Наименование</b>' . self::$MCE . '</style>',
                    self::$MCS . '<b>Плановое кол-во корпуса</b>' . self::$MCE,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<b>План</b>' . self::$MCE,
                    '',
                    '',
                    self::$MCS . '<b>Зефирная масска, кг</b>' . self::$MCE,
                    '',
                    self::$MCS . '<b>ПРИМЕЧАНИЕ</b>' . self::$MCE,
                    self::$MCS . '<b>Факт</b>' . self::$MCE,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<b>ПРИМЕЧАНИЕ</b>' . self::$MCE
                ],
                [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<wraptext><b>кол-во людей</b></wraptext>' . self::$MCE,
                    self::$MCS . '<b>Время, ч</b>' . self::$MCE,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<wraptext><b>кол-во людей</b></wraptext>' . self::$MCE,
                    self::$MCS . '<b>Время, ч</b>' . self::$MCE
                ],
                [
                    '<style height="57"></style>',
                    '',
                    self::$MCS . '<b>ящ</b>' . self::$MCE,
                    self::$MCS . '<b>шт</b>' . self::$MCE,
                    self::$MCS . '<b>кг</b>' . self::$MCE,
                    self::$MCS . '<b>Варка</b>' . self::$MCE,
                    self::$MCS . '<b>Телеги</b>' . self::$MCE,
                    '',
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<b>начало</b>' . self::$MCE,
                    self::$MCS . '<b>окончание</b>' . self::$MCS,
                    '',
                    '',
                    '',
                    self::$MCS . '<b>ящ</b>' . self::$MCE,
                    self::$MCS . '<b>шт</b>' . self::$MCE,
                    self::$MCS . '<b>кг</b>' . self::$MCE,
                    self::$MCS . '<b>Варка</b>' . self::$MCE,
                    self::$MCS . '<b>Телеги</b>' . self::$MCE,
                    '',
                    '',
                    '',
                    '',
                    self::$MCS . '<b>начало</b>' . self::$MCE,
                    self::$MCS . '<b>окончание</b>' . self::$MCS,
                    '',
                    self::$MCS . '<b>Чел-часов по плану</b>' . self::$MCE,
                    self::$MCS . '<b>Чел-часов по закрытой ГП</b>' . self::$MCE,
                    self::$MCS . '<b>Чел-часов по факту</b>' . self::$MCE
                ]
            ];
    }

    public function loadOrder(Request $request)
    {
        if (!$request->files) {
            return 'Файл не предоставлен';
        }

        if ($xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath())) {

            $curCat = null;
            $unrecognized = [];
            $amounts = [];
            foreach($xlsx->rows(0) as $k => $row) {
                if ($row['1'] == 'Итог') {
                    break;
                }
                // Ловим категории
                if ($category = ProductsCategoriesController::getByName($row[1])) {
                    $curCat = $category;
                    continue;
                }
                // Ловим продукты
                if ($curCat && ($product = ProductsDictionary::where('title', $row[1])->first())){
                    $amounts[] = [
                        'product_id'    => $product->product_id,
                        'amount'        => $row[3]
                    ];
                    continue;
                } else if ($curCat && !strtotime($row[1]) && ($row[2]||$row[3]||$row[4])) {
                    $product = ProductsDictionary::create([
                        'title'         => $row[1],
                        'category_id'   => $curCat->category_id
                    ]);
                    $amounts[] = [
                        'product_id'    => $product->product_id,
                        'amount'        => $row[3]
                    ];
                    continue;
                }
                if ($curCat && !strtotime($row[1])) {
                    $unrecognized[$k] = $row[1];
                }
            }

            foreach ($amounts as &$amount) {
                if (($val = $amount['amount']) && $amount['amount'] > 0) {
                    $rec = ProductsOrder::withSession($request)
                        ->updateOrCreate(
                            ['product_id'   => $amount['product_id'],
                            'isDay'         => $request->attributes->get('isDay'),
                            'date'          => $request->attributes->get('date')
                        ],
                            ['amount' => $val]
                        );
                    $amount['order_id'] = $rec->order_id;
                }
            }
            return Util::successMsg([
                'uncategorized' => $unrecognized, 
                'amounts'       => $amounts
            ], 201);
        }
    }
    public function getPlans(Request $request)
    {
        $session = Util::getSessionAsArray($request);
        $plans = ProductsPlan::withSession($request)->get();

        $lines = [];
        $products = [];
        $slots = [];

        // $plans->each(function($plan) {
        //     $slots[] = $plan->slot;
        //     $lines[] = $plan->slot->line_id;
        //     $products[] = ProductsDictionary::find($plan->slot->product_id);
        // });

        $responsibles = Responsible::get(['responsible_id', 'title']);

        // $linesFromPlans = array_unique(array_map(function ($el) {
        //     return $el['line_id'];
        // }, $plans));
        // $productsFromLines = array_unique(array_map(function ($el) {
        //     return $el['product_id'];
        // }, $plans));
        // $slotsFromProducts = array_unique(array_map(function ($el) {
        //     return $el['slot_id'];
        // }, $plans));

        // $r = json_decode(ResponsibleController::getList(), true);
        // $responsibles = [];
        // foreach ($r as $f) {
        //     $responsibles[$f['responsible_id']] = $f['title'];
        // }

        // $lines = json_decode(LinesController::getList($request), true);
        // $lines = array_filter($lines, function ($el) use ($linesFromPlans) {
        //     return in_array($el['line_id'], $linesFromPlans);
        // });
        // $products = ProductsDictionary::whereIn('product_id', $productsFromLines)->get(['product_id', 'title', 'amount2parts', 'parts2kg', 'kg2boil', 'cars', 'cars2plates'])->toArray();
        // $slots = ProductsSlots::whereIn('product_slot_id', $slotsFromProducts)->get(['product_slot_id', 'people_count', 'perfomance', 'product_id'])->toArray();

        // foreach ($products as &$prod) {
        //     $slot = array_filter($slots, function ($el) use ($prod) {
        //         return $el['product_id'] === $prod['product_id'];
        //     });
        //     $slot = reset($slot);
        //     $prod['people_count'] = $slot['people_count'];
        //     $prod['perfomance'] = $slot['perfomance'];
        // }

        // $linesFiltered = [];
        // // Варка
        // $linesFiltered[0] = array_filter($lines, function ($el) {
        //     return $el['type_id'] == 1;
        // });
        // // Упаковка
        // $linesFiltered[1] = array_filter($lines, function ($el) {
        //     return $el['type_id'] == 2;
        // });
        // unset($lines);

        $arr = [
            1 => self::makeArrayHeader($session), 
            2 => self::makeArrayHeader($session)
        ];

        $linesSheets = [
            1 => [],
            2 => []
        ];
        Lines::each(function($line) use (&$linesSheets) {
            if ($line->plans->isNotEmpty()) {
                $linesSheets[$line->type_id][] = $line;
            }
        });

        foreach ($linesSheets as $sheet => &$lines) {
            $array = $arr[$sheet];
            $dateCount = 0;
            foreach ($lines as &$line) {
                $line['started_at'] = Carbon::parse($line['started_at']);
                $line['ended_at'] = Carbon::parse($line['ended_at']);
                $linePlans = $line->plans->sortBy('started_at');

                // $linePlans = array_map(function ($el) use ($products) {
                //     $prod_id = array_search(
                //         $el['product_id'],
                //         array_column($products, 'product_id')
                //     );

                //     if ($prod_id !== false) {
                //         $el['title'] = $products[$prod_id]['title'];
                //         $el['amount2parts'] = $products[$prod_id]['amount2parts'] ? $products[$prod_id]['amount2parts'] : 1;
                //         $el['parts2kg'] = $products[$prod_id]['parts2kg'] ? $products[$prod_id]['parts2kg'] : 1;
                //         $el['kg2boil'] = $products[$prod_id]['kg2boil'] ? $products[$prod_id]['kg2boil'] : 1;
                //         $el['cars'] = $products[$prod_id]['cars'] ? $products[$prod_id]['cars'] : 1;
                //         $el['cars2plates'] = $products[$prod_id]['cars2plates'] ? $products[$prod_id]['cars2plates'] : 1;
                //         $el['perfomance'] = $products[$prod_id]['perfomance'] ? $products[$prod_id]['perfomance'] : 1;
                //         $el['people_count'] = $products[$prod_id]['people_count'] ? $products[$prod_id]['people_count'] : 1;
                //     }

                //     return $el;
                // }, $linePlans);
                
                // array_multisort(
                //     array_column($linePlans, 'position'),
                //     SORT_ASC,
                //     $linePlans
                // );
                // $line['started_at'] = $linePlans[0]['started_at'];
                // $line['ended_at'] = (last($linePlans))['ended_at'];
                // var_dump($line->line_id);
                $line = $line->toArray() + (
                    LinesExtra::withSession($request)->where('line_id', $line->line_id)->first()->toArray()
                );
                $line['items'] = [];
                $hardwares = array_unique($linePlans->map(function($item) {
                    return $item->slot->hardware;
                })->toArray());

                if (count($hardwares) != 0) {
                    foreach ($hardwares as $hw) {
                        $line['items'][$hw] = [
                            'hwTitle' => self::$hardware[$hw],
                            'items' => []
                        ];
                    }
                    $linePlans->each(function($p) use (&$line) {
                        $line['items'][$p->slot->hardware]['items'][] = $p->toArray() + $p->slot->product->toArray();
                    });
                    // $line['items']
                } else {
                    $line['items'][0] = [
                        'items' => $linePlans
                    ];
                }

                $line['master'] = $line['master'] ? explode(' ', Responsible::find($line['master'])->title) : '';
                $line['engineer'] = $line['engineer'] ? explode(' ', Responsible::find($line['engineer'])->title) : '';


                if (is_array($line['master'])) {
                    $line['master'] = $line['master'][0] . '.' . mb_substr($line['master'][1], 0, 1) . '.';
                }
                if (is_array($line['engineer'])) {
                    $line['engineer'] = $line['engineer'][0] . '.' . mb_substr($line['engineer'][1], 0, 1) . '.';
                }
                $array[] = ['', '<style bgcolor="#D8E4BC"><b>' . $line['title'] . '</b> (' . $line['extra_title'] . ')</style>', '', '', '', '', '', '', '', '', '', $line['workers_count'], $line['started_at']->format('H:i'), $line['ended_at']->format("H:i")];
                if ($line['has_detector']) {
                    $array[] = ['', '<style bgcolor="#fc8c03"><b><i>МЕТАЛОДЕТЕКТОР</i></b></style>', '', '', '', '', '', '', '', '', '', '', $line['detector_start'], $line['detector_end']];
                }
                $array[] = ['', '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $line['master'] . ',' . $line['engineer'] . '</b></style>'];
                if ($line['prep_time'] != 0) {
                    $array[] = ['', '<style bgcolor="#FFC263"><b><i>Подготовительное время</i></b></style>', '', '', '', '', '', '', '', '', '', '', $line['started_at']->format("H:i"), $line['started_at']->addMinutes($line['prep_time'])->format('H:i')];
                }

                $sum = [
                    'z' => [0, 0],
                    's' => [0, 0],
                    'k' => [0, 0]
                ];

                foreach ($line['items'] as &$hw) {
                    if (isset($hw['hwTitle'])) {    
                        $array[] = ['', '<style bgcolor="#D8E4BC"><b>' . mb_strtoupper($hw['hwTitle']) . '</b></style>'];
                    }
                    $colons = array_map(function($i){ return $i['colon']; }, $hw['items']);

                    $colons = array_filter(array_unique($colons));
                    if (count($colons) > 1 || array_search(3, $colons) !== false) {
                        $array[] = ['', '<b>' . self::$colons[3] . '</b>'];
                    } else {
                        $array[] = ['', '<b>' . self::$colons[array_shift($colons)] . '</b>'];
                    }
                    
                    // usort($hw['items'], function ($a, $b) {
                    //     return strtotime($a['started_at']) <=> strtotime($b['started_at']);
                    // });                   
                    
                    foreach ($hw['items'] as $product) {
                        $crates = intval($product['amount']);
                        $parts = ceil(eval("return $crates*$product[amount2parts];"));
                        $kg = ceil(eval("return $parts*$product[parts2kg];"));
                        $boils = ceil(eval("return $kg*$product[kg2boil];"));
                        $prec =  eval("return $boils*$product[cars];");
                        $cars = floor($prec);
                        $plates = eval("return ($prec - $cars)*$product[cars2plates];");
                        // var_dump(10);
                        // die();

                        // eval('$parts = ceil(' . $crates . '*' . $product['amount2parts'] . ');');
                        // eval('$kg = ceil(' . $parts . '*' . $product['parts2kg'] . ');');
                        // eval('$boils = ' . $kg . '*' . $product['kg2boil'] . ';');

                        if (mb_strpos(mb_strtolower($product['title']), 'зефир') !== false) {
                            $sum['z'][0] += $kg;
                            $sum['z'][1] += $boils;
                        } else if (mb_strpos(mb_strtolower($product['title']), 'суфле') !== false) {
                            $sum['s'][0] += $kg;
                            $sum['s'][1] += $boils;
                        } else if (mb_strpos(mb_strtolower($product['title']), 'конфет') !== false) {
                            $sum['k'][0] += $kg;
                            $sum['k'][1] += $boils;
                        } else {
                            // Если не сработал ни один паттерн, считаем, что это зефир
                            $sum['z'][0] += $kg;
                            $sum['z'][1] += $boils;
                        }

                        // eval('$prec =  ' . $boils . '*' . $product['cars'] . ';');
                        // $cars = floor($prec);
                        // eval('$plates = ' . ($prec - $cars) . '*' . $product['cars2plates'] . ';');

                        // var_dump($product);
                        // die();
                        $array[] = [
                            '',
                            $product['title'],
                            self::$MCS . $crates . self::$MCE,
                            self::$MCS . $parts . self::$MCE,
                            self::$MCS . $kg . self::$MCE,
                            self::$MCS . $boils . self::$MCE,
                            self::$MCS . $cars . self::$MCE,
                            '<b>т</b>',
                            // $prec,
                            self::$MCS . ceil($plates) . self::$MCE,
                            '<b>под</b>',
                            self::$MCS . '<b>' . $prec . '</b>' . self::$MCE,
                            $product['slot']['people_count'],
                            Carbon::parse($product['started_at'])->format('H:i'),
                            Carbon::parse($product['ended_at'])->format('H:i'),
                            '',
                            '<f>=R' . (count($array) + 1) . '*' . $product['amount2parts'],
                            '<f>=S' . (count($array) + 1) . '*' . $product['parts2kg'],
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $kg / $product['slot']['perfomance'] * $product['slot']['people_count'],
                            '<f>=T' . (count($array) + 1) . '/' . $product['slot']['perfomance'] . '*' . $product['slot']['people_count'] . '</f>'
                        ];

                        $dateCount += $crates + $parts;
                    }
                }
                if ($line['after_time'] != 0) {
                    $lastTime = Carbon::parse($array[count($array)-1][13]);
                    $array[] = ['', '<style bgcolor="#FFC263"><b><i>Заключительное время</i></b></style>', '', '', '', '', '', '', '', '', '', '', $lastTime->format('H:i:s'), $lastTime->addMinutes($line['after_time'])->format('H:i:s')];
                }
                $array[] = [];

                $array[] = ['', '<b>Итого зефира</b>', '', '', $sum['z'][0], $sum['z'][1]];
                $array[] = ['', '<b>Итого суфле</b>', '', '', $sum['s'][0], $sum['s'][1]];
                $array[] = ['', '<b>Итого конфет</b>', '', '', $sum['k'][0], $sum['k'][1]];
                $array[] = ['', '<b>Отходы</b>'];
                $sum = [
                    'z' => [0, 0],
                    's' => [0, 0],
                    'k' => [0, 0]
                ];

                $array[] = [];
            }

            if($sheet == 1) {
                $dating = LinesExtra::withSession($request)
                    ->where('line_id', 42)
                    // ->with(['lines'])
                    ->first()
                    ->toArray();
                //     Lines::find(42)->toArray(),
                //     LinesExtraController::get($, $isDay, 42)->toArray()
                // );

                // var_dump($dating);

                $array[] = ['', '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $dating['master'] . ',' . $dating['engineer'] . '</b></style>'];
                $array[] = ['', '<style bgcolor="#D8E4BC"><b>ДАТИРОВАНИЕ</b></style>', '', ($dateCount / 8000), '', '', '', '', '', '', '', $dating['workers_count'], Carbon::parse($dating['started_at'])->format("H:i"), Carbon::parse($dating['ended_at'])->format("H:i")];

            }
            $arr[$sheet] = $array;
        }

        // die();


        $xlsx = SimpleXLSXGen::fromArray($arr[1], 'Варка')
            ->setDefaultFontSize(20)
            ->setColWidth(1, 10)
            ->setColWidth(2, 34)
            ->setColWidth(3, 8)
            ->setColWidth(4, 8)
            ->setColWidth(5, 8)
            ->setColWidth(6, 8)
            ->setColWidth(7, 8)
            ->setColWidth(9, 8)
            ->setColWidth(11, 0)
            ->setColWidth(12, 8)
            ->setColWidth(15, 0)
            ->setColWidth(16, 0)
            ->setColWidth(17, 0)
            ->setColWidth(26, 8)
            ->setColWidth(29, 20)
            ->mergeCells('A4:A6')
            ->mergeCells('B4:B6')
            ->mergeCells('C4:J5')
            ->mergeCells('L4:N4')
            ->mergeCells('G6:J6')
            ->mergeCells('L5:L6')
            ->mergeCells('V6:Y6')
            ->mergeCells('Z3:AC3')
            ->mergeCells('AA5:AB5')
            ->mergeCells('Z5:Z6')
            ->mergeCells('R4:AB4')
            ->mergeCells('AC4:AC6')
            ->mergeCells('M5:N5')
            ->addSheet($arr[2], 'Упаковка')
            ->setDefaultFontSize(20)
            ->setColWidth(1, 10)
            ->setColWidth(2, 34)
            ->setColWidth(3, 8)
            ->setColWidth(4, 8)
            ->setColWidth(5, 8)
            ->setColWidth(6, 8)
            ->setColWidth(7, 8)
            ->setColWidth(9, 8)
            ->setColWidth(11, 0)
            ->setColWidth(12, 8)
            ->setColWidth(15, 0)
            ->setColWidth(16, 0)
            ->setColWidth(17, 0)
            ->setColWidth(26, 8)
            ->setColWidth(29, 20)
            ->mergeCells('A4:A6')
            ->mergeCells('B4:B6')
            ->mergeCells('C4:J5')
            ->mergeCells('L4:N4')
            ->mergeCells('G6:J6')
            ->mergeCells('L5:L6')
            ->mergeCells('V6:Y6')
            ->mergeCells('Z3:AC3')
            ->mergeCells('AA5:AB5')
            ->mergeCells('Z5:Z6')
            ->mergeCells('R4:AB4')
            ->mergeCells('AC4:AC6')
            ->mergeCells('M5:N5');
        
        $name = 'План_' . date('d_m_Y', strtotime($session['date'])) . '.xlsx';
        $xlsx->downloadAs($name);

        // return $name;
    }

    public function getFile(Request $request)
    {
        $data = $request->post();

        $lines = Lines::all();
        foreach ($lines as $line) {
            $line['slots'] = Slots::where('line_id', '=', $line['line_id'])->get()->toArray();
        }

        $columns = [
            ['<b><i>Наряд за</i></b>', date('d.m.Y H.i.s', time()), '', '', '', '', '', ''],
            array_fill(0, 8, ''),
            [
                'Список рабочих',
                'Отработано часов по плану',
                'Отработано часов по факту',
                'Простои',
                'Итого часов',
                'КТУ',
                'Итого часов с КТУ',
                'Примечание'
            ]
        ];
        foreach ($lines as $line) {
            if ($line['slots'] && count($line['slots']) > 0) {
                $columns[] = [
                    '<style bgcolor="' . ($line['color'] ? $line['color'] : '#1677ff') . '">' . $line['title'] . '</style>',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ];

                $count = count($columns);
                // $line
                foreach ($line['slots'] as $slot) {
                    $worker = Workers::find($slot['worker_id']);
                    /**
                     * Обработать все слоты, на которых работал человек, а не каждый отдельно
                     */
                    $workTime = self::setFloat(self::getWorkTime($slot['started_at'], $slot['ended_at']));
                    $ktu = $data[array_search($slot['worker_id'], array_column($data, 'worker_id'))]['ktu'];
                    if (!$worker) {
                        // var_dump($slot);
                    }
                    $columns[] = [
                        $worker['title'],
                        // self::setFloat($slot['time_planned'] / 60),
                        $workTime,
                        '',
                        self::setFloat($slot['down_time'] / 60),
                        $workTime + self::setFloat(($slot['down_time'] / 60)),
                        $ktu,
                        $ktu * ($workTime + self::setFloat($slot['down_time'] / 60))
                    ];
                }
                $count1 = count($columns);
                $columns[] = [
                    '<style bgcolor="#FDE9D9">ИТОГО</style>',
                    '<style bgcolor="#FDE9D9">' . self::summarize(array_column($columns, 1), $count, $count1) . '<style bgcolor="#FDE9D9">',
                    '<style bgcolor="#FDE9D9"><style bgcolor="#FDE9D9">',
                    '<style bgcolor="#FDE9D9">' . self::summarize(array_column($columns, 3), $count, $count1) . '<style bgcolor="#FDE9D9">',
                    '<style bgcolor="#FDE9D9">' . self::summarize(array_column($columns, 4), $count, $count1) . '<style bgcolor="#FDE9D9">',
                    '',
                    '<style bgcolor="#FDE9D9">' . self::summarize(array_column($columns, 6), $count, $count1) . '<style bgcolor="#FDE9D9">',
                ];
            }
        }
        $columns[] = [''];
        $columns[] = ['КОМПАНИИ'];

        $companies = Workers::select('company')->distinct()->get();
        foreach ($companies as $company) {
            $workers = array_column(
                Workers::where('company', '=', $company->company)->get(['title'])->toArray(),
                'title'
            );

            $arr = array_filter($columns, function ($el) use ($workers) {
                if (array_search($el[0], $workers) !== false) {
                    return $el;
                }
            });

            $columns[] = [
                $company->company,
                array_sum(array_column($arr, 1)),
                '',
                // array_sum(array_column($arr, 2)),
                array_sum(array_column($arr, 3)),
                array_sum(array_column($arr, 4)),
                array_sum(array_column($arr, 5)),
                array_sum(array_column($arr, 6))
            ];
        }


        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Отчёт_' . date('d_m_Y-H_i_s', time()) . '.xlsx';

        $xlsx->saveAs($name);
        return $name;
        // return $xlsx->download();
    }
    static private function getWorkTime($start, $end)
    {
        $start = new \DateTime($start);
        $end = new \DateTime($end);
        $diff = $start->diff($end);
        return $diff->h + ($diff->i / 60);
    }
    static private function setFloat(float $num)
    {
        return number_format((float) $num, 2, '.', '');
    }
    static private function summarize(array $arr, int $start, int $end)
    {
        if (count($arr) < $end) {
            return 0;
        }
        $result = 0;
        for ($i = $start; $i < $end; $i++) {
            $result += floatval($arr[$i]);
        }
        return $result;
    }

    // static public function getPlans(){
    //     $plans = [];
    //     try {
    //     ProductsPlan::chunk(50, function($planArray) use (&$plans) {
    //         $planArray->each(function($plan) use (&$plans) {
    //             if (!isset($plans[strval($plan->date) . ':' . $plan->isDay])) {
    //                 $plans[strval($plan->date) . ':' . $plan->isDay] = [
    //                     'date' => $plan->date,
    //                     'isDay' => $plan->isDay,
    //                     'plan' => true,
    //                     'order' => count(ProductsOrder::where('date', $plan->date)->get()->toArray()) > 0,
    //                     'workers' => count(Slots::where('date', $plan->date)->get()->toArray()) > 0
    //                 ];
    //             }
    //         });
    //     });
    //     ProductsOrder::chunk(50, function($orderArray) use (&$plans) {
    //         $orderArray->each(function($order) use (&$plans){
    //             if (!isset($plans[strval($order->date) . ':' . $order->isDay])) {
    //                 $plans[strval($order->date) . ':' . $order->isDay] = [
    //                     'date' => $order->date,
    //                     'isDay' => $order->isDay,
    //                     'plan' => false,
    //                     'order' => true,
    //                     'workers' => count(Slots::where('date', $order->date)->get()->toArray()) > 0
    //                 ];
    //             }
    //         });
    //     });
    //     Slots::chunk(50, function($slotsArray) use (&$plans) {
    //         $slotsArray->each(function($slot) use (&$plans) {
    //             if (!isset($plans[strval($slot->date) . ':' . $slot->isDay])) {
    //                 $plans[strval($slot->date) . ':' . $slot->isDay] = [
    //                     'date' => $slot->date,
    //                     'isDay' => $slot->isDay,
    //                     'plan' => false,
    //                     'order' => false,
    //                     'workers' => true
    //                 ];
    //             }
    //         });
    //     });
    //     }catch(Exception $e) {
    //         var_dump($e);
    //         return $e;
    //     }
    //     return json_encode(array_values($plans));
    // }
}
