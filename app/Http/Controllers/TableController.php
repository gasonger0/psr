<?php

namespace App\Http\Controllers;

use App\Models\Companies;
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
use DateTime;
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
            foreach ($xlsx->rows(0) as $k => $row) {
                if ($row['1'] == 'Итог') {
                    break;
                }
                // Ловим категории
                if ($category = ProductsCategoriesController::getByName($row[1])) {
                    $curCat = $category;
                    continue;
                }
                // Ловим продукты
                if ($curCat && ($product = ProductsDictionary::where('title', $row[1])->first())) {
                    $amounts[] = [
                        'product_id' => $product->product_id,
                        'amount' => $row[3]
                    ];
                    continue;
                } else if ($curCat && !strtotime($row[1]) && ($row[2] || $row[3] || $row[4])) {
                    $product = ProductsDictionary::create([
                        'title' => $row[1],
                        'category_id' => $curCat->category_id
                    ]);
                    $amounts[] = [
                        'product_id' => $product->product_id,
                        'amount' => $row[3]
                    ];
                    continue;
                }
                if ($curCat && !strtotime($row[1])) {
                    $unrecognized[$k] = $row[1];
                }
            }

            // Чистим прошлый анализ
            ProductsOrder::withSession($request)->each(function ($p) {
                $p->delete();
            });

            foreach ($amounts as &$amount) {
                if (($val = $amount['amount']) && $amount['amount'] > 0) {
                    $rec = ProductsOrder::withSession($request)
                        ->updateOrCreate(
                            [
                                'product_id' => $amount['product_id'],
                                'isDay' => $request->attributes->get('isDay'),
                                'date' => $request->attributes->get('date')
                            ],
                            ['amount' => $val]
                        );
                    $amount = [
                        'order' => [
                            'order_id' => $rec->order_id,
                            'product_id' => $rec->product_id,
                            'amount' => $rec->amount,
                        ],
                        'product' => ProductsDictionary::find($rec->product_id)->toArray()
                    ];
                }
            }
            return Util::successMsg([
                'uncategorized' => $unrecognized,
                'amounts' => $amounts
            ], 201);
        }
    }
    public function getPlans(Request $request)
    {
        // Подготовка
        $session = Util::getSessionAsArray($request);
        $lines = [];
        $products = [];
        $slots = [];

        $responsibles = Responsible::get(['responsible_id', 'title']);

        // Создаём шапки листов
        $arr = [
            1 => self::makeArrayHeader($session),
            2 => self::makeArrayHeader($session)
        ];

        // Распределяем линии по листам
        $linesSheets = [
            1 => [],
            2 => []
        ];
        Lines::each(function ($line) use (&$linesSheets, $session) {
            $pls = array_filter($line->plans->toArray(), fn($p) => $p['date'] == $session['date'] && $p['isDay'] == $session['isDay']);
            if ($pls) {
                $linesSheets[$line->type_id][] = $line;
            }
        });

        // Обработка листов
        foreach ($linesSheets as $sheet => &$lines) {
            $array = $arr[$sheet];
            $dateCount = 0;
            $dateCountNew = [];
            $returnMassCells = [];
            $globalZ = 0;
            $globalB = 0;
            // Обработка линий на листе
            foreach ($lines as &$line) {
                // Получаем планы со всех смен (пока хз как исправить)
                $linePlans = $line->plans->sortBy('started_at');

                // Переводим линию в массив и добавляем данные со смены
                $line = $line->toArray() + (
                    LinesExtra::withSession($request)->where('line_id', $line->line_id)->first()->toArray()
                );
                $line['started_at'] = Carbon::parse($line['started_at']);
                $line['ended_at'] = Carbon::parse($line['ended_at']);
                $line['items'] = [];

                // Собираем список оборудования с планов
                $hardwares = array_unique($linePlans->map(function ($item) {
                    return $item->hardware;
                })->toArray());

                // Обрабатываем оборудование с линий
                if (count($hardwares) != 0) {
                    foreach ($hardwares as $hw) {
                        $line['items'][$hw] = [
                            'hwTitle' => self::$hardware[$hw],
                            'items' => []
                        ];
                    }
                    // Группируем планы по оборудованию 
                    $linePlans->each(function ($p) use (&$line, $session) {
                        if ($p['date'] == $session['date'] && $p['isDay'] == $session['isDay']) {
                            $line['items'][$p->hardware]['items'][] =
                                $p->toArray() +
                                $p->slot->product->toArray() +
                                ['slot' => $p->slot];
                        }
                    });
                } else {
                    $line['items'][0] = [
                        'items' => $linePlans
                    ];
                }

                // Делаем ФИО Отсветсвенных 
                // TODO нужны только на упаковке
                $line['master'] = $line['master'] ? explode(' ', Responsible::find($line['master'])->title) : '';
                $line['engineer'] = $line['engineer'] ? explode(' ', Responsible::find($line['engineer'])->title) : '';
                if (is_array($line['master'])) {
                    $line['master'] = $line['master'][0] . '.' . mb_substr($line['master'][1], 0, 1) . '.';
                }
                if (is_array($line['engineer'])) {
                    $line['engineer'] = $line['engineer'][0] . '.' . mb_substr($line['engineer'][1], 0, 1) . '.';
                }

                // Делаем шапку линии
                $array[] = self::makeRow([
                    1 => "<style bgcolor=\"#D8E4BC\"><b>$line[title]</b></style>",
                    4 => ($line['extra_title'] ? "($line[extra_title])" : ""),
                    11 => $line['workers_count'],
                    12 => "<b>" . $line['started_at']->format('H:i') . "</b>",
                    13 => "<b>" . $line['ended_at']->format("H:i") . "</b>"
                ]);

                // Ставим детектор
                if (
                    $line['has_detector'] &&
                    $line['type_id'] == 2 &&
                    $line['detector_start'] &&
                    $line['detector_end']
                ) {
                    $array[] = self::makeRow([
                        1 => '<style bgcolor="#FC8C03"><b><i>МЕТАЛЛОДЕТЕКТОР</i></b></style>',
                        12 => $line['detector_start'],
                        13 => $line['detector_end']
                    ]);
                }

                // Ответственные
                if ($line['master'] || $line['engineer']) {
                    $array[] = self::makeRow([
                        1 =>
                            "<style bgcolor=\"#B7DEE8\"><b>" .
                            "ОТВЕТСТВЕННЫЕ: $line[master], $line[engineer]" .
                            "</b></style>"
                    ]);
                }

                if ($line['prep_time'] != 0) {
                    $array[] = self::makeRow([
                        1 => '<style bgcolor="#FFC263"><b><i>Подготовительное время</i></b></style>',
                        11 => $line['workers_count'],
                        12 => $line['started_at']->format("H:i"),
                        13 => $line['started_at']
                            ->addMinutes($line['prep_time'])
                            ->format('H:i')
                    ]);
                }

                // Суммы по Зефиру, Суфле и Конфетам
                $sum = [
                    'z' => [0, 0],
                    's' => [0, 0],
                    'k' => [0, 0]
                ];

                // Обрабатываем оборудование
                foreach ($line['items'] as &$hw) {
                    if (isset($hw['hwTitle'])) {
                        $array[] = [
                            '',
                            '<style bgcolor="#D8E4BC"><b>' . mb_strtoupper($hw['hwTitle']) . '</b></style>'
                        ];
                    }
                    // Выделяем колонки
                    if ($line['type_id'] == 1) {
                        $colons = array_map(fn($i) => $i['colon'], $hw['items']);

                        $colons = array_filter(array_unique($colons));
                        if (count($colons) > 1 || array_search(3, $colons) !== false) {
                            $array[] = ['', '<b>' . self::$colons[3] . '</b>'];
                        } else {
                            $array[] = ['', '<b>' . self::$colons[array_shift($colons)] . '</b>'];
                        }
                    }

                    // Обработка продукции
                    foreach ($hw['items'] as $product) {
                        $row_index = count($array) + 1;

                        // Расчёты

                        /*$counts = [
                            2 => intval($product['amount']),
                            3 => "ОКРУГЛ(C$row_index*$product[amount2parts];0)",
                            4 => "ОКРУГЛ(D$row_index*$product[parts2kg];0)",
                            5 => "E$row_index*$product[kg2boil]",
                            6 => "ОКРУГЛВНИЗ(K$row_index, 0)",
                            8 => "(K$row_index - H$row_index)*$product[cars2plates]",
                            10 => "F$row_index*$product[cars]>"
                        ];

                        foreach ($counts as $k => &$row) {
                            $row = self::$MCS . $row . self::$MCE;
                        }*/
                        try {
                            $crates = intval($product['amount']);
                            $parts = ceil(eval ("return $crates*$product[amount2parts];"));
                            $kg = ceil(eval ("return $parts*$product[parts2kg];"));
                            $boils = isset($product['kg2boil']) ? ceil(eval ("return $kg*$product[kg2boil];")) : 0;
                            $prec = isset($product['cars']) ? eval ("return $boils*$product[cars];") : 0;
                            $cars = floor($prec);
                            $plates = isset($product['cars2plates']) ? eval ("return ($prec - $cars)*$product[cars2plates];") : 0;
                        } catch (Exception $e) {
                            return Util::errorMsg("Проверьте формулы для " . $product['title']);
                        }

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



                        /*$array[] = self::makeRow([
                            1 => $product['title'],
                            7 => '<b>т</b>',
                            9 => '<b>под</b>',
                            11 => $product['slot']['people_count'],
                            12 => Carbon::parse($product['started_at'])->format('H:i'),
                            13 => Carbon::parse($product['ended_at'])->format('H:i'),
                            15 => "<f>=R$row_index * {$product['amount2parts']}</f>",
                            16 => "<f>=S$row_index * {$product['parts2kg']}</f>",
                            29 => "<f>=E$row_index / {$product['slot']['perfomance']} * {$product['slot']['people_count']}</f>",
                            30 => "<f>=T$row_index / {$product['slot']['perfomance']} * {$product['slot']['people_count']}</f>"
                        ] + $counts);

                        $dateCountNew[] = "C$row_index";
                        $dateCountNew[] = "D$row_index";*/

                        $array[] = self::makeRow([
                            1 => $product['title'],
                            2 => self::$MCS . $crates . self::$MCE,
                            3 => self::$MCS . $parts . self::$MCE,
                            4 => self::$MCS . $kg . self::$MCE,
                            5 => self::$MCS . $boils . self::$MCE,
                            6 => self::$MCS . $cars . self::$MCE,
                            7 => '<b>т</b>',
                            8 => self::$MCS . ceil($plates) . self::$MCE,
                            9 => '<b>под</b>',
                            10 => self::$MCS . '<b>' . $prec . '</b>' . self::$MCE,
                            11 => $product['slot']['people_count'],
                            12 => Carbon::parse($product['started_at'])->format('H:i'),
                            13 => Carbon::parse($product['ended_at'])->format('H:i'),
                            15 => '<f>=R' . (count($array) + 1) . '*' . $product['amount2parts'],
                            16 => '<f>=S' . (count($array) + 1) . '*' . $product['parts2kg'],
                            29 => $kg / $product['slot']['perfomance'] * $product['slot']['people_count'],
                            30 => '<f>=T' . (count($array) + 1) . '/' . $product['slot']['perfomance'] . '*' . $product['slot']['people_count'] . '</f>'
                        ]);


                        $dateCount += $crates + $parts;

                    }
                }
                if ($line['after_time'] != 0) {
                    $lastTime = Carbon::parse($line['ended_at']);
                    $array[] = self::makeRow([
                        1 => '<style bgcolor="#FFC263"><b><i>Заключительное время</i></b></style>',
                        11 => $line['workers_count'],
                        12 => $lastTime->addMinutes(-$line['after_time'])->format('H:i:s'),
                        13 => $lastTime->addMinutes($line['after_time'])->format('H:i:s')
                    ]);
                }
                $array[] = [];

                if ($line['type_id'] == 2) {
                    if ($sum['z'][0] > 0) {
                        $val = Util::calcReturnMass($line, $sum['z'][0], 'z');
                        if ($val != false) {
                            $array[] = ["", "Возвратные отходы зеф.массы:", '', '', "<i>$val</i>"];
                            $returnMassCells[] = "E" . count($array);
                        }
                    }

                    if ($sum['s'][0] > 0) {
                        $val = Util::calcReturnMass($line, $sum['s'][0], 's');
                        if ($val != false) {
                            $array[] = ["", "Возвратные отходы суфле:", '', '', "<i>$val</i>"];
                            // $returnMassCells[] = "E" . count($array);
                        }
                    }

                    $array[] = [];
                } else {
                    // TODO Возможно, для линий варки тоже надо не для всех
                    $array[] = ["", "Возвратные отходы зеф.массы:"];
                    $returnMassCells[] = "E" . count($array);
                }

                $array[] = ['', '<b>Итого зефира</b>', '', '', $sum['z'][0], $sum['z'][1]];
                $array[] = ['', '<b>Итого суфле</b>', '', '', $sum['s'][0], $sum['s'][1]];
                $array[] = ['', '<b>Итого конфет</b>', '', '', $sum['k'][0], $sum['k'][1]];
                $array[] = ['', '<b>Отходы</b>'];
                $globalZ += $sum['z'][0];
                $globalB += $sum['z'][1];
                $sum = [
                    'z' => [0, 0],
                    's' => [0, 0],
                    'k' => [0, 0]
                ];

                $array[] = [];
            }

            if ($sheet == 1) {
                // Датирование
                $dating = LinesExtra::withSession($request)
                    ->where('line_id', 42)
                    ->first()
                    ->toArray();

                $array[] = ['', '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $dating['master'] . ',' . $dating['engineer'] . '</b></style>'];
                $array[] = self::makeRow([
                    1 => '<style bgcolor="#D8E4BC"><b>ДАТИРОВАНИЕ</b></style>',
                    3 => ($dateCount / 8000),
                    11 => $dating['workers_count'],
                    12 => Carbon::parse($dating['started_at'])->format("H:i"),
                    13 => Carbon::parse($dating['ended_at'])->format("H:i")
                ]);

            }
            $array[] = [];
            $array[] = self::makeRow([
                1 => "ИТОГО ЗЕФИРА",
                4 => $globalZ,
                5 => $globalB

            ]);
            $array[] = self::makeRow([
                1 => "ИТОГО ВОЗВРАТНОЙ МАССЫ",
                4 => "<f>=" . implode(" + ", $returnMassCells)
            ]);

            array_push(
                $array,
                [],
                [],
                ['', "<b><i>ЗАДАНИЕ СОСТАВИЛ</i></b>"],
                ['', "<b><i>ЗАДАНИЕ ПОЛУЧИЛ</i></b>"]
            );
            $arr[$sheet] = $array;
            $globalZ = 0;
            $returnMassCells = [];
            $dateCount = 0;
        }

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
        $session = Util::getSessionAsArray($request);

        $dateString =
            (new DateTime($session['date']))->format('d.m.Y') . '_' . ($session['isDay'] == 1 ? 'день' : 'ночь');

        $lines = Lines::all()->toArray();
        foreach ($lines as &$line) {
            $line['slots'] = Slots::where('line_id', '=', $line['line_id'])
                ->withSession($request)
                ->groupBy(['worker_id', 'slot_id'])
                ->get()
                ->toArray();
            // var_dump(Slots::where('line_id', '=', $line['line_id'])->groupBy(['worker_id', 'slot_id'])->toRawSql());
        }
        // var_dump($lines);
        // die();
        $companies = [];
        $sum = array_fill(1, 6, []);
        $sumByLines = array_fill(1, 6, []);

        foreach (Companies::get() as $comp) {
            $companies[$comp->company_id] = [
                'title' => $comp->title,
                'indexes' => []
            ];
        }

        $columns = [
            ['<b><i>Наряд за</i></b>', $dateString, '', '', '', '', '', ''],
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

        // Получаем объём изготовления по каждой из продукций на линиях
        $times = Util::getLinesPersonalTime($request);


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
                    '',
                ];

                $count = count($columns) + 1;

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
                    $row_num = count($columns) + 1;
                    $row = [
                        $worker->title,
                        // self::setFloat($slot['time_planned'] / 60),
                        $workTime,
                        0,
                        self::setFloat($slot['down_time'] / 60),
                        "<f>=C$row_num - D$row_num</f>",
                        $ktu,
                        "<f>=E$row_num * F$row_num</f>"
                    ];

                    $columns[] = $row;

                    $companies[$worker->company_id]['indexes'][] = $row_num;

                    // Человек от данной компании на конкретной линии
                    if (!isset($companies[$worker->company_id]['lines'][$line['line_id']])) {
                        $companies[$worker->company_id]['lines'][$line['line_id']] = 0;
                    }
                    $companies[$worker->company_id]['lines'][$line['line_id']] += 1;
                }
                $count1 = count($columns);
                $columns[] = [
                    '<style bgcolor="#FDE9D9">ИТОГО</style>',
                    '<style bgcolor="#FDE9D9">' . "<f>=SUM(B$count:B$count1)</f>" . '</style>',
                    '<style bgcolor="#FDE9D9">' . "<f>=SUM(C$count:C$count1)</f>" . '</style>',
                    '<style bgcolor="#FDE9D9">' . "<f>=SUM(D$count:D$count1)</f>" . '</style>',
                    '<style bgcolor="#FDE9D9">' . "<f>=SUM(E$count:E$count1)</f>" . '</style>',
                    '',
                    '<style bgcolor="#FDE9D9">' . "<f>=SUM(G$count:G$count1)</f>" . '</style>',
                ];

                // Человек суммарно на данной линии
                if (isset($times[$line['line_id']])) {
                    $times[$line['line_id']]['totalPeople'] = $count1 - $count + 1;
                }
                foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $k => $i) {
                    $sum[$k + 1][] = $i . count($columns);
                    $sumByLines[$k + 1][] = $i . $count - 1;
                }
            }
        }

        $columns[3][7] = "ТОННАЖ ПЛАН";
        $columns[4][7] = array_sum(
            array_map(
                function ($i) {

                    return $i['amount']['s'] +
                        $i['amount']['z'] +
                        $i['amount']['k'];
                },
                $times
            )
        );
        $columns[5][7] = "ТОННАЖ ФАКТ";
        $columns[7][7] = "ОТКЛОНЕНИЕ";
        $columns[8][7] = "<f>=H5-H7";
        $columns[] = [''];
        $sumByLines = array_map(fn($i) => "<f>=" . implode(" + ", $i), $sumByLines);
        $columns[] = ["ИТОГО ПО ЗАДАНИЮ", ...$sumByLines];
        $sum = array_map(fn($i) => "<f>=" . implode(" + ", $i), $sum);
        $columns[] = ["ИТОГО ПО РАСПИСАННЫМ ЛЮДЯМ", ...$sum];
        $columns[] = [];
        $columns[] = [
            "КОМПАНИИ",
            "Отработано часов по плану",
            "Отработано часов по факту",
            "Простои",
            "Итого часов",
            "КТУ",
            "Итого часов с КТУ",
            "Тоннаж (зефир)",
            "Тоннаж (конфеты)",
            "Тоннаж (суфле)"
        ];

        foreach ($companies as $company) {
            $amount = [
                's' => [],
                'z' => [],
                'k' => []
            ];
            if (isset($company['lines'])) {
                foreach ($company['lines'] as $line_id => $people_count) {
                    if (isset($times[$line_id])) {
                        foreach (['s', 'z', 'k'] as $index) {
                            $amount[$index][] = $times[$line_id]['amount'][$index] . "/" .
                                $times[$line_id]['totalPeople'] . "*" .
                                $people_count;
                        }
                    }
                }
            }

            $columns[] = [
                $company['title'],
                self::summarize($company['indexes'], 'B'),
                self::summarize($company['indexes'], 'C'),
                self::summarize($company['indexes'], 'D'),
                self::summarize($company['indexes'], 'E'),
                '',
                // self::summarize($company['indexes'], 'F'),
                self::summarize($company['indexes'], 'G'),
                count($amount['z']) > 0 ? "<f>=" . implode("+", $amount['z']) : '',
                count($amount['k']) > 0 ? "<f>=" . implode("+", $amount['k']) : '',
                count($amount['s']) > 0 ? "<f>=" . implode("+", $amount['s']) : '',
            ];
        }

        array_push(
            $columns,
            [],
            [],
            ["Заместитель генерального директора", '', '', '', '', '', "Корнилова Л.А."],
            ["Начальник производства"],
            ["Начальник смены"],
            ["Мастер варки"]
        );
        // Тоннаж: масса по линии * кол-во человек по компаниям (count($company['indexes']) / сумму)

        // for ( $i = $counter; $i < count($columns); $i++) {
        //     $columns[$i][7] = "<f>=".
        // }


        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Отчёт_' . $dateString . '.xlsx';

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
    static private function summarize(array $arr, string $letter)
    {
        if (count($arr) == 0) {
            return '';
        }
        foreach ($arr as &$index) {
            $index = $letter . $index;
        }
        return '<f>=(' . implode('+', $arr) . ')</f>';
        // return '<f>=(' . implode('+', $arr) . ')/' . count($arr) . '</f>';
    }

    private static function makeRow(array $items): array
    {
        $new = array_fill(0, 30, '');
        foreach ($items as $k => $v) {
            $new[$k] = $v;
        }
        return $new;
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
