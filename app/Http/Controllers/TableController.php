<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\Products_categories;
use App\Models\ProductsDictionary;
use App\Models\ProductsOrder;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\Responsible;
use App\Models\Slots;
use App\Models\Workers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class TableController extends Controller
{
    private $file = [];
    static $MCS = '<center><middle>';
    static $MCE = '</middle></center>';
    private static $skipPhrases = ['подготовительное время', 'заключительное время'];
    private static $colons = ['', 'Варочная колонка №1', 'Варочная колонка №2'];
    private static $hardware = [
        0 => 'Без оборудования', 
        1 => 'Мондомикс', 
        2 => 'Торнадо', 
        3 => 'Китайский аэрос', 
        4 => 'Завёрточная машина №1', 
        5 => 'Завёрточная машина №2', 
        6 => 'Завёрточные машины №1, №2'
    ];
    
    private static function makeArrayHeader($date, $isDay)
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
                    '<style height="52">' . date('d_m_Y', strtotime($date)) . '</style>'
                ],
                [
                    '<style height="52">Смена:</style>',
                    $isDay ? 'День' : 'Ночь',
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

    public function loadFile(Request $request)
    {
        /*
        if (!$request->files) {
            return 'Файл не предоставлен';
        }

        if ($xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath())) {
            $this->file['products'] = $xlsx->rows(0);
            $this->file['workers'] = $xlsx->rows(1);
        }

        LinesController::clear();
        ProductsController::clear();
        WorkersController::clear();
        SlotsController::clear();

        if ($this->file['products'] != []) {
            $this->processProducts();
        }

        if ($this->file['workers'] != []) {
            $this->processWorkers();
        }

        return 0;
        */
    }
    public function loadOrder(Request $request)
    {
        if (!$request->files) {
            return 'Файл не предоставлен';
        }

        if ($xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath())) {

            // ProductsOrder::truncate();
            $date = $request->cookie('date');
            $isDay = boolval($request->cookie('isDay'));
            $cats = Products_categories::get(['title', 'category_id'])->toArray();
            $products = ProductsDictionary::get(['title', 'product_id', 'category_id'])->toArray();
            foreach ($cats as &$cat) {
                $cat['title'] = mb_strtoupper($cat['title']);
            }
            $curCat = null;
            $unrecognized = [];
            $amounts = [];
            $rows = $xlsx->rows(0);
            foreach ($rows as $k => $row) {
                $category_index = array_search(mb_strtoupper($row[1]), array_column($cats, 'title'));
                if ($category_index !== false) {
                    $curCat = $cats[$category_index];
                    continue;
                }
                if ($curCat) {
                    $product_index = array_search(
                        $row[1],
                        array_column($products, 'title')
                    );
                    if ($product_index !== false) {
                        $amounts[] = [
                            'product_id' => $products[$product_index]['product_id'],
                            'amount' => $row[3]
                        ];
                        continue;
                    } else if ($row[0] != null) {
                        $prod = new ProductsDictionary();
                        $prod->title = $row[1];
                        $prod->category_id = $curCat['category_id'];
                        $prod->save();
                        $amounts[] = [
                            'product_id' => $prod->product_id,
                            'amount' => $row[3]
                        ];
                        continue;
                    }
                }
                if ($curCat && !strtotime($row[1])) {
                    $unrecognized[$k] = $row[1];
                }
            }
            foreach ($amounts as $amount) {
                if ($val = $amount['amount']) {
                    $am = ProductsOrder::where('product_id', '=', $amount['product_id'])
                        ->where('date', $date)
                        ->where('isDay', $isDay)
                        ->get();
                    if ($am->count() > 0) {
                        foreach ($am as $i) {
                            $i->amount = $val;
                            $i->save();
                        }
                    } else {
                        $order = new ProductsOrder();
                        $order->product_id = $amount['product_id'];
                        $order->amount = $val;
                        $order->date = $date;
                        $order->isDay = $isDay;
                        $order->save();
                    }
                }
            }
            return json_encode([$unrecognized, $amounts]);
        }
    }

    public function loadDefaults(Request $request)
    {
        $xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath());
        if ($xlsx) {
            $categories = Products_categories::all(['category_id', 'title'])->toArray();

            $categories = array_map(function ($el) {
                $el['title'] = mb_strtoupper($el['title']);
                return $el;
            }, $categories);
            // $products = ProductsDictionary::all(['product_id', 'title'])->toArray();
            // ProductsDictionaryController::clear();
            // ProductsSlotsController::clear();

            $activeCategory = null;
            $activeProduct = null;
            $lastLine = null;
            foreach ($xlsx->rows() as $k => $row) {
                if ($row[1] == 'Примечание') {
                    break;
                }
                if ($k < 13) {
                    continue;
                }
                if ($row[0] != '') {
                    // Product
                    $activeProduct = ProductsDictionary::insertGetId([
                        'title' => $row[1],
                        'category_id' => $activeCategory['category_id']
                    ]);
                } else if ($row[1] != '') {
                    // Cat
                    $index = array_search(
                        mb_strtoupper($row[1]),
                        array_column($categories, 'title')
                    );

                    if ($index !== false) {
                        $activeCategory = $categories[$index];
                    }
                    continue;
                }

                // Slots
                $line_id = Lines::where('title', '=', $row[4])
                    ->first()->line_id ?? null;
                $isHardware = $row[4] == 'ТОРНАДО';

                if (!$line_id && $isHardware) {
                    $line_id = $lastLine;
                }
                if ($line_id) {
                    $lastLine = $line_id;
                    $boil = ProductsSlots::where('product_id', '=', $activeProduct)
                        ->where('line_id', '=', $line_id)->where('hardware', ($isHardware ? '!=' : '='), null)
                        ->first();
                    if (!$boil) {
                        $boil = new ProductsSlots();
                    }
                    $boil->product_id = $activeProduct;
                    $boil->line_id = $line_id;
                    $boil->people_count = ($row[8] != '') ? $row[8] : 0;
                    $boil->perfomance = $row[5] != '' ? doubleval($row[5]) : 0;
                    $boil->type_id = 1;
                    if ($isHardware) {
                        $boil->hardware = 1;
                    }
                    $boil->save();
                }

                $pack = array_slice($row, 14, 4 * 5);
                $pack = array_chunk($pack, 4);
                foreach ($pack as $el) {
                    if (!$el[0]) {
                        continue;
                    }
                    $line_id = Lines::where('title', '=', $el[0])
                        ->first()->line_id ?? null;
                    if ($line_id) {
                        $slot = ProductsSlots::where('product_id', '=', $activeProduct)
                            ->where('line_id', '=', $line_id)
                            ->first();
                        if ($slot) {
                            continue;
                        } else {
                            $slot = new ProductsSlots();
                            $slot->product_id = $activeProduct;
                            $slot->line_id = $line_id;
                            $slot->people_count = intval($el[2]) ?? 0;
                            $slot->perfomance = doubleval($el[1]) ?? 0;
                            $slot->type_id = 2;
                            $slot->save();
                        }
                    } else {
                        print_r('Not found line: ' . $el[0]);
                    }
                }
            }
        }
    }
    public function loadFormulas(Request $request)
    {
        $xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath());
        $arr = [];

        foreach ($xlsx->rowsEx() as $k => $row) {
            $i = ProductsDictionary::where('title', '=', $row[1]['value'])->first();
            if ($i) {
                if ($row[3]['f']) {
                    $i->amount2parts = preg_filter('/[A-Z]\d{1,4}./', '', $row[3]['f']);
                }
                if ($row[4]['f']) {
                    $i->parts2kg = preg_filter('/[A-Z]\d{1,4}./', '', $row[4]['f']);
                }
                if ($row[5]['f']) {
                    $i->kg2boil = preg_filter('/[A-Z]\d{1,4}./', '', $row[5]['f']);
                }
                if ($row[8]['f']) {
                    $i->cars2plates = preg_filter('/\([A-Z]\d{1,4}-[A-Z]\d{1,4}\)./', '', $row[8]['f']);
                }
                if ($row[12]['f']) {
                    $i->cars = preg_filter('/[A-Z]\d{1,4}./', '', $row[12]['f']);
                }
                $i->save();
            }
        }
        return $arr;
    }
    public function dowloadForPrint(Request $request)
    {
        $date = $request->cookie('date');
        $isDay = boolval($request->cookie('isDay'));
        $plans = json_decode(ProductsPlanController::getList($request), true);

        $linesFromPlans = array_unique(array_map(function ($el) {
            return $el['line_id'];
        }, $plans));
        $productsFromLines = array_unique(array_map(function ($el) {
            return $el['product_id'];
        }, $plans));
        $slotsFromProducts = array_unique(array_map(function ($el) {
            return $el['slot_id'];
        }, $plans));

        $r = json_decode(ResponsibleController::getList(), true);
        $responsibles = [];
        foreach ($r as $f) {
            $responsibles[$f['responsible_id']] = $f['title'];
        }

        $lines = json_decode(LinesController::getList($request), true);
        $lines = array_filter($lines, function ($el) use ($linesFromPlans) {
            return in_array($el['line_id'], $linesFromPlans);
        });
        $products = ProductsDictionary::whereIn('product_id', $productsFromLines)->get(['product_id', 'title', 'amount2parts', 'parts2kg', 'kg2boil', 'cars', 'cars2plates'])->toArray();
        $slots = ProductsSlots::whereIn('product_slot_id', $slotsFromProducts)->get(['product_slot_id', 'people_count', 'perfomance', 'product_id'])->toArray();

        foreach ($products as &$prod) {
            $slot = array_filter($slots, function ($el) use ($prod) {
                return $el['product_id'] === $prod['product_id'];
            });
            $slot = reset($slot);
            $prod['people_count'] = $slot['people_count'];
            $prod['perfomance'] = $slot['perfomance'];
        }

        $linesFiltered = [];
        // Варка
        $linesFiltered[0] = array_filter($lines, function ($el) {
            return $el['type_id'] == 1;
        });
        // Упаковка
        $linesFiltered[1] = array_filter($lines, function ($el) {
            return $el['type_id'] == 2;
        });
        unset($lines);

        $arr = [self::makeArrayHeader($date, $isDay), self::makeArrayHeader($date, $isDay)];


        foreach ($linesFiltered as $sheet => &$lines) {
            $array = $arr[$sheet];
            $dateCount = 0;
            foreach ($lines as &$line) {
                $linePlans = array_filter($plans, function ($el) use ($line) {
                    return $el['line_id'] == $line['line_id'];
                });

                $linePlans = array_map(function ($el) use ($products) {
                    $prod_id = array_search(
                        $el['product_id'],
                        array_column($products, 'product_id')
                    );

                    if ($prod_id !== false) {
                        $el['title'] = $products[$prod_id]['title'];
                        $el['amount2parts'] = $products[$prod_id]['amount2parts'] ? $products[$prod_id]['amount2parts'] : 1;
                        $el['parts2kg'] = $products[$prod_id]['parts2kg'] ? $products[$prod_id]['parts2kg'] : 1;
                        $el['kg2boil'] = $products[$prod_id]['kg2boil'] ? $products[$prod_id]['kg2boil'] : 1;
                        $el['cars'] = $products[$prod_id]['cars'] ? $products[$prod_id]['cars'] : 1;
                        $el['cars2plates'] = $products[$prod_id]['cars2plates'] ? $products[$prod_id]['cars2plates'] : 1;
                        $el['perfomance'] = $products[$prod_id]['perfomance'] ? $products[$prod_id]['perfomance'] : 1;
                        $el['people_count'] = $products[$prod_id]['people_count'] ? $products[$prod_id]['people_count'] : 1;
                    }

                    return $el;
                }, $linePlans);
                
                array_multisort(
                    array_column($linePlans, 'position'),
                    SORT_ASC,
                    $linePlans
                );
                // $line['started_at'] = $linePlans[0]['started_at'];
                // $line['ended_at'] = (last($linePlans))['ended_at'];
                $line['items'] = [];
                $hardwares = array_unique(array_column($linePlans, 'hardware'));

                if (count($hardwares) != 0) {
                    foreach ($hardwares as $hw) {
                        $line['items'][$hw] = [
                            'hwTitle' => self::$hardware[$hw],
                            'items' => array_filter($linePlans, function ($el) use ($hw) {
                                return $el['hardware'] == $hw;
                            })
                        ];
                    }
                } else {
                    $line['items'][0] = [
                        'items' => $linePlans
                    ];
                }

                $line['master'] = $line['master'] ? explode(' ', $responsibles[$line['master']]) : '';
                $line['engineer'] = $line['engineer'] ? explode(' ', $responsibles[$line['engineer']]) : '';


                if (is_array($line['master'])) {
                    $line['master'] = $line['master'][0] . '.' . mb_substr($line['master'][1], 0, 1) . '.';
                }
                if (is_array($line['engineer'])) {
                    $line['engineer'] = $line['engineer'][0] . '.' . mb_substr($line['engineer'][1], 0, 1) . '.';
                }
                $array[] = ['', '<style bgcolor="#D8E4BC"><b>' . $line['title'] . '</b> (' . $line['extra_title'] . ')</style>', '', '', '', '', '', '', '', '', '', $line['workers_count'], $line['started_at'], $line['ended_at']];
                if ($line['has_detector']) {
                    $array[] = ['', '<style bgcolor="#fc8c03"><b><i>МЕТАЛОДЕТЕКТОР</i></b></style>', '', '', '', '', '', '', '', '', '', '', $line['detector_start'], $line['detector_end']];
                }
                $array[] = ['', '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $line['master'] . ',' . $line['engineer'] . '</b></style>'];
                if ($line['prep_time'] != 0) {
                    $array[] = ['', '<style bgcolor="#FFC263"><b><i>Подготовительное время</i></b></style>', '', '', '', '', '', '', '', '', '', '', $line['started_at'], Carbon::parse($line['started_at'])->addMinutes($line['prep_time'])->format('H:i:s')];
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
                    $colons = [];
                    foreach ($hw['items'] as $h){
                        $c = explode(';', $h['colon']);
                        $colons = array_merge($colons, $c);
                    }
                    $colon = array_filter(array_unique($colons));
                    if (count($colon) >= 2) {
                        $colon = [1, 2];
                    } else {
			            $colon = array_values($colon);
		            }
                    if (!empty($colon)) {
                        $array[] = ['', '<b>'. self::$colons[$colon[0]] . (isset($colon[1]) ? ', ' . self::$colons[$colon[1]] : '') . '</b>'];
                        array_shift($colon);
                    }

                    
                    usort($hw['items'], function ($a, $b) {
                        return strtotime($a['started_at']) <=> strtotime($b['started_at']);
                    });                   
                    
                    foreach ($hw['items'] as $product) {
                        $crates = intval($product['amount']);
                        eval('$parts = ceil(' . $crates . '*' . $product['amount2parts'] . ');');
                        eval('$kg = ceil(' . $parts . '*' . $product['parts2kg'] . ');');
                        eval('$boils = ' . $kg . '*' . $product['kg2boil'] . ';');

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

                        eval('$prec =  ' . $boils . '*' . $product['cars'] . ';');
                        $cars = floor($prec);
                        eval('$plates = ' . ($prec - $cars) . '*' . $product['cars2plates'] . ';');
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
                            $product['workers_count'],
                            $product['started_at'],
                            $product['ended_at'],
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
                            $kg / $product['perfomance'] * $product['people_count'],
                            '<f>=T' . (count($array) + 1) . '/' . $product['perfomance'] . '*' . $product['people_count'] . '</f>'
                        ];

                        $dateCount+= ($crates + $parts);
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
                $dating = array_merge(
                    Lines::find(42)->toArray(),
                    LinesExtraController::get($date, $isDay, 42)->toArray()
                );

                $array[] = ['', '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $dating['master'] . ',' . $dating['engineer'] . '</b></style>'];
                $array[] = ['', '<style bgcolor="#D8E4BC"><b>ДАТИРОВАНИЕ</b></style>', '', ($dateCount / 8000), '', '', '', '', '', '', '', $dating['workers_count'], $dating['started_at'], $dating['ended_at']];

            }
            $arr[$sheet] = $array;
        }

        // die();


        $xlsx = SimpleXLSXGen::fromArray($arr[0], 'Варка')
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
            ->addSheet($arr[1], 'Упаковка')
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
        
        $name = 'План_' . date('d_m_Y', strtotime($date)) . '.xlsx';
        $xlsx->downloadAs($name);

        // return $name;
    }
    public function loadPlan(Request $request)
    {
        /*
        $path = $request->files->get('file')->getRealPath();
        $data = json_decode(file_get_contents($path), true);
        if ($data['plans']) {
            ProductsPlanController::clear();

            foreach ($data['plans'] as $item) {
                $i = new ProductsPlan();
                $i->product_id = $item['product_id'];
                $i->line_id = $item['line_id'];
                $i->slot_id = $item['slot_id'];
                $i->started_at = $item['started_at'];
                $i->ended_at = $item['ended_at'];
                $i->type_id = $item['type_id'];
                $i->amount = $item['amount'];
                $i->save();
            }
        }
        if ($data['lines']) {
            foreach ($data['lines'] as $item) {
                $i = Lines::find($item['line_id']);
                if ($i) {
                    // $i->master = $item['master'] ?? null;
                    // $i->started_at = $item['started_at'] ?? null;
                    // $i->ended_at = $item['ended_at'] ?? null;
                    // $i->color = $item['color'] ?? null;
                    // $i->engineer = $item['engineer'] ?? null;
                    $i->type_id = $item['type_id'] ?? null;
                    $i->save();
                }
            }
        }
        LogsController::clear();
        return;
        */
    }

    private function processProducts()
    {
        // $currentLine = null;
        // foreach (array_slice($this->file['products'], 4) as $row) {
        //     if (
        //         $row[1] != null &&
        //         $row[2] != null &&
        //         $row[3] != null &&
        //         $row[4] != null
        //     ) {
        //         if ($row[0] == null) {
        //             // Строка линии
        //             if (array_search(trim(strtolower($row[1])), self::$skipPhrases) === false) {
        //                 $currentLine = LinesController::add(
        //                     trim($row[1]),
        //                     trim($row[2]),
        //                     trim($row[3]),
        //                     trim($row[4])
        //                 );
        //             } else {
        //                 continue;
        //             }
        //         } else {
        //             // Строка продукта
        //             if (array_search(trim(strtolower($row[1])), self::$skipPhrases) === false) {
        //                 ProductsController::add(
        //                     trim($row[0]),
        //                     $currentLine,
        //                     trim($row[1]),
        //                     trim($row[2]),
        //                     trim($row[3]),
        //                     trim($row[4])
        //                 );
        //             } else {
        //                 continue;
        //             }
        //         }
        //     } else {
        //         continue;
        //     }
        // }
    }
    // private function processWorkers()
    // {
    //     $lines = json_decode(LinesController::getList(['line_id', 'title']), true);
    //     $lineCells = array_slice($this->file['workers'][0], 4);
    //     for ($i = 0; $i < count($lineCells); $i += 2) {
    //         if (!empty($lineCells[$i]) && ($index = array_search($lineCells[$i], array_column($lines, 'title'))) !== false) {
    //             $lines[$index]["cells"] = [$i, $i + 1];
    //         }
    //     }
    //     foreach (array_slice($this->file['workers'], 3) as $row) {
    //         if ($row[1] == null)
    //             continue;
    //         $worker_id = WorkersController::add($row[0], $row[1], $row[2], $row[3]);
    //         $row = array_slice($row, 4);
    //         // $time = 0;      // time in minutes
    //         for ($m = 0; $m < count($row); $m += 2) {
    //             if (($index = array_search([$m, $m + 1], array_column($lines, 'cells'))) !== false) {
    //                 if ($row[$m] == null)
    //                     continue;
    //                 SlotsController::add($lines[$index]['line_id'], $worker_id, $row[$m], $row[$m + 1]);
    //                 // $bufdiff = (new \DateTime($row[$m]))->diff(new \DateTime($row[$m+1]));
    //                 //$time += $bufdiff->h * 60 + $bufdiff->i;
    //                 // WorkersController::updateBaseTime($worker_id, $time);
    //                 // $time = 0;
    //             }
    //         }
    //     }
    // }

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

    static public function getPlans(){
        $plans = [];
        ProductsPlan::all()->each(function($plan) use (&$plans) {
            if (!isset($plans[strval($plan->date) . ':' . $plan->isDay])) {
                $plans[strval($plan->date) . ':' . $plan->isDay] = [
                    'date' => $plan->date,
                    'isDay' => $plan->isDay,
                    'plan' => true,
                    'order' => count(ProductsOrder::where('date', $plan->date)->get()->toArray()) > 0,
                    'workers' => count(Slots::where('date', $plan->date)->get()->toArray()) > 0
                ];
            }
        });
        ProductsOrder::all()->each(function($order) use (&$plans) {
            if (!isset($plans[strval($order->date) . ':' . $order->isDay])) {
                $plans[strval($order->date) . ':' . $order->isDay] = [
                    'date' => $order->date,
                    'isDay' => $order->isDay,
                    'plan' => false,
                    'order' => true,
                    'workers' => count(Slots::where('date', $order->date)->get()->toArray()) > 0
                ];
            }
        });
        Slots::all()->each(function($slot) use (&$plans) {
            if (!isset($plans[strval($slot->date) . ':' . $slot->isDay])) {
                $plans[strval($slot->date) . ':' . $slot->isDay] = [
                    'date' => $slot->date,
                    'isDay' => $slot->isDay,
                    'plan' => false,
                    'order' => false,
                    'workers' => true
                ];
            }
        });
        return json_encode(array_values($plans));
    }
}
