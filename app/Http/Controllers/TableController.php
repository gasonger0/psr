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
        2 => 'Мондомикс',
        1 => 'Торнадо',
        3 => 'Китайский аэрос',
        4 => 'Завёрточная машина №1',
        5 => 'Завёрточная машина №2',
        6 => 'Завёрточные машины №1, №2'
    ];

    private static function makeArrayHeader($session, $type)
    {
        return
            [
                self::makeRow([
                    2 => self::$MCS . '2' . self::$MCE,
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
                    self::$MCS . '28' . self::$MCE,
                ]),
                [
                    '<style height="52">Дата</style>',
                    '<style height="52">' . $session['date'] . '</style>'
                ],
                self::makeRow([
                    0 => '<style height="52">Смена:</style>',
                    $session['isDay'] ? 'День' : 'Ночь',
                    3 => 'план:',
                    15 => 'факт:',
                    21 => self::$MCS . 'Зам. ген.директора ООО КФ "Сокол"' . self::$MCE,
                ]),
                self::makeRow([
                    0 => self::$MCS . '<b>№</b>' . self::$MCE,
                    '<style border="#000000" font-size="20">' .
                    self::$MCS .
                    '<b>Наименование</b>' .
                    self::$MCE .
                    '</style>',
                    self::$MCS . '<b>Плановое кол-во корпуса</b>' . self::$MCE,
                    11 => self::$MCS . '<b>План</b>' . self::$MCE,
                    // 10 => self::$MCS . '<b>Зефирная масса, кг</b>' . self::$MCE,
                    // 16 => self::$MCS . '<b>ПРИМЕЧАНИЕ</b>' . self::$MCE,
                    15 => self::$MCS . '<b>Факт</b>' . self::$MCE,
                    27 => self::$MCS . '<b>ПРИМЕЧАНИЕ</b>' . self::$MCE,
                ]),
                self::makeRow([
                    11 => self::$MCS . '<wraptext><b>кол-во людей</b></wraptext>' . self::$MCE,
                    12 => self::$MCS . '<b>Время, ч</b>' . self::$MCE,
                    24 => self::$MCS . '<wraptext><b>кол-во людей</b></wraptext>' . self::$MCE,
                    25 => self::$MCS . '<b>Время, ч</b>' . self::$MCE,
                ]),
                self::makeRow([
                    0 => '<style height="57"></style>',
                    2 => self::$MCS . '<b>ящ</b>' . self::$MCE,
                    self::$MCS . '<b>шт</b>' . self::$MCE,
                    self::$MCS . '<b>кг</b>' . self::$MCE,
                    self::$MCS . ($type == 1 ? '<b>Варка</b>' : '') . self::$MCE,
                    self::$MCS . '<b>Телеги</b>' . self::$MCE,
                    12 => self::$MCS . '<b>начало</b>' . self::$MCE,
                    self::$MCS . '<b>окончание</b>' . self::$MCS,
                    15 => self::$MCS . '<b>ящ</b>' . self::$MCE,
                    self::$MCS . '<b>шт</b>' . self::$MCE,
                    self::$MCS . '<b>кг</b>' . self::$MCE,
                    self::$MCS . ($type == 1 ? '<b>Варка</b>' : '') . self::$MCE,
                    self::$MCS . '<b>Телеги</b>' . self::$MCE,
                    25 => self::$MCS . '<b>начало</b>' . self::$MCE,
                    self::$MCS . '<b>окончание</b>' . self::$MCS,
                    28 => self::$MCS . '<b>Чел-часов по плану</b>' . self::$MCE,
                    self::$MCS . '<b>Чел-часов по закрытой ГП</b>' . self::$MCE,
                    self::$MCS . '<b>Чел-часов по факту</b>' . self::$MCE
                ])
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
            1 => self::makeArrayHeader($session, 1),
            2 => self::makeArrayHeader($session, 2)
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
            $dateCount = [];
            $returnMassCells = [
                'z' => [],
                'k' => [],
                's' => []
            ];
            $globalKG = [
                'z' => [],
                'k' => [],
                's' => []
            ];
            $globalB = [
                'z' => [],
                'k' => [],
                's' => []
            ];
            // Обработка линий на листе
            foreach ($lines as &$line) {
                // Получаем планы на текущую смену, отсортированные по времени начала
                $linePlans = $line->plans()
                    ->where('date', $session['date'])
                    ->where('isDay', $session['isDay'])
                    ->orderBy('started_at')
                    ->get();

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
                    $linePlans->each(function ($p) use (&$line) {
                        $line['items'][$p->hardware]['items'][] =
                            $p->toArray() +
                            $p->slot->product->toArray() +
                            ['category' => $p->slot->product->category->toArray()] +
                            ['slot' => $p->slot];
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
                    $line['master'] = $line['master'][0] ." ". (count($line['master']) > 1 ? (mb_substr($line['master'][1], 0, 1) . '.') : "");
                }
                if (is_array($line['engineer'])) {
                    $line['engineer'] = $line['engineer'][0] ." ". (count($line['engineer']) > 1 ? (mb_substr($line['engineer'][1], 0, 1) . '.') : "");
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
                    'z' => [[], []],
                    's' => [[], []],
                    'k' => [[], []]
                ];
                // Строки продуктов по категориям (для формул факта в итогах)
                $catRows = [
                    'z' => [],
                    's' => [],
                    'k' => []
                ];

                // Обрабатываем оборудование
                foreach ($line['items'] as &$hw) {
                    if (isset($hw['hwTitle']) && $line['type_id'] == 1) {
                        $array[] = self::makeRow([
                            1 => '<style bgcolor="#D8E4BC"><b>' . mb_strtoupper($hw['hwTitle']) . '</b></style>'
                        ]);
                    }
                    // Выделяем колонки
                    if ($line['type_id'] == 1) {
                        $colons = array_map(fn($i) => $i['colon'], $hw['items']);

                        $colons = array_filter(array_unique($colons));
                        if (count($colons) > 1 || array_search(3, $colons) !== false) {
                            $array[] = self::makeRow([1 => '<b>' . self::$colons[3] . '</b>']);
                        } else {
                            $array[] = self::makeRow([1 => '<b>' . self::$colons[array_shift($colons)] . '</b>']);
                        }
                    }

                    // Обработка продукции
                    foreach ($hw['items'] as $product) {
                        $row_index = count($array) + 1;

                        $counts = array_replace(
                            Util::makeCounts($row_index, $product, 'B', $product['amount']),
                            [
                                1 => $product['title'],
                                11 => $product['slot']['people_count'],
                                12 => Carbon::parse($product['started_at'])->format('H:i'),
                                13 => Carbon::parse($product['ended_at'])->format('H:i'),
                                28 => "<f>=E$row_index / {$product['slot']['perfomance']} * {$product['slot']['people_count']}</f>",
                                29 => "<f>=R$row_index / {$product['slot']['perfomance']} * {$product['slot']['people_count']}</f>",
                            ],
                            Util::makeCounts($row_index, $product, 'O')
                        );

                        $category = $product['category']['title'];

                        $cat = 'z'; // по умолчанию зефир
                        if (
                            mb_strpos(mb_strtolower($category), 'зефир') !== false ||
                            mb_strpos(mb_strtolower($product['title']), 'зефир') !== false
                        ) {
                            $cat = 'z';
                        } else if (
                            mb_strpos(mb_strtolower($category), 'суфле') !== false ||
                            mb_strpos(mb_strtolower($product['title']), 'суфле') !== false
                        ) {
                            $cat = 's';
                        } else if (
                            mb_strpos(mb_strtolower($category), 'конфет') !== false ||
                            mb_strpos(mb_strtolower($product['title']), 'конфет') !== false
                        ) {
                            $cat = 'k';
                        }
                        $sum[$cat][0][] = "E$row_index";
                        $sum[$cat][1][] = "F$row_index";

                        $array[] = self::makeRow($counts);
                        array_push($dateCount, "C$row_index", "D$row_index");

                        // Запоминаем строку продукта для формул факта в итогах
                        if ($cat == "z" && $sheet == 1) {
                            if (array_search($line['line_id'], [8, 9, 10, 11, 12]) !== false) {
                                $catRows[$cat][] = $row_index;
                            }
                        } else {
                            $catRows[$cat][] = $row_index;
                        }

                    }
                }
                if ($line['after_time'] != 0) {
                    $lastTime = Carbon::parse($line['ended_at']);
                    $array[] = self::makeRow([
                        1 => '<style bgcolor="#FFC263"><b><i>Заключительное время</i></b></style>',
                        11 => $line['workers_count'],
                        12 => $lastTime->addMinutes(-$line['after_time'])->format('H:i'),
                        13 => $lastTime->addMinutes($line['after_time'])->format('H:i')
                    ]);
                }
                $array[] = [];

                if ($sheet == 2) {
                    foreach ([
                        'z' => 'зеф.массы',
                        's' => 'суфле',
                        'k' => 'конфет'
                    ] as $i => $t) {
                        if (count($sum[$i][0]) > 0) {
                            $val = Util::calcReturnMass($line, $sum[$i][0], $i);
                            if ($val != false) {
                                $array[] = self::makeRow([1 => "Возвратные отходы $t:", 4 => "<i>$val</i>"]);
                                $returnMassCells[$i][] = "E" . count($array);
                            }
                        }
                    }

                    $array[] = [];
                } else {
                    // TODO Возможно, для линий варки тоже надо не для всех
                    $array[] = self::makeRow([1 => "Возвратные отходы зеф.массы:"]);
                    $returnMassCells['z'][] = "E" . count($array);
                }

                $add = function ($i, $sum) use (&$globalB, &$globalKG) {
                    array_push($globalKG[$i], ...$sum[$i][0]);
                    array_push($globalB[$i], ...$sum[$i][1]);
                };

                foreach (['z', 's', 'k'] as $i) {

                    $array[] = self::makeRow(Util::makeResult($i, $sum, $catRows, $line['type_id'] == 1));

                    /* Для упаковки:
                        1) Зефир - считаем только зефир на нл1 сп, нл2 сп, нл1 гл, нл2 гл, шл1, па, эквивалент
                        2) Конфеты - только по ваншоту
                        3) Суфле - только по шл1
                    */
                    if ($sheet == 2) {
                        switch ($i) {
                            case 'z':
                                if (array_search($line['line_id'], [14, 17, 18, 20, 24, 25, 41]) !== false) {
                                    $add($i, $sum);
                                }
                                break;
                            case 'k':
                                if ($line['line_id'] == 31) {
                                    $add($i, $sum);
                                }
                                break;
                            case 's':
                                if ($line['line_id'] == 20) {
                                    $add($i, $sum);
                                }
                                break;
                        }
                    } else {
                        if (
                            $i == 'z' && array_search($line['line_id'], [8, 9, 10, 11, 12]) !== false
                            && (str_contains($product['title'], 'начинка') === false
                                || str_contains($product['title'], 'переваривание') === false)
                        ) {
                            $add($i, $sum);
                        }
                    }
                }

                $sum = [
                    'z' => [0, 0],
                    's' => [0, 0],
                    'k' => [0, 0]
                ];

                $array[] = [];
            }

            if ($sheet == 2) {
                // Датирование
                $dating = LinesExtra::withSession($request)
                    ->where('line_id', 42)
                    ->first()
                    ->toArray();

                $array[] = self::makeRow([
                    1 => '<style bgcolor="#D8E4BC"><b>ДАТИРОВАНИЕ</b></style>',
                    3 => "<f>=" . implode("+", $dateCount) . " / 8000</f>",
                    11 => $dating['workers_count'],
                    12 => Carbon::parse($dating['started_at'])->format("H:i"),
                    13 => Carbon::parse($dating['ended_at'])->format("H:i")
                ]);
                if ($dating['master'] || $dating['engineer']) {
                    $array[] = [
                        '',
                        '<style bgcolor="#B7DEE8"><b>ОТВЕТСТВЕННЫЕ: ' . $dating['master'] . ',' . $dating['engineer'] . '</b></style>'
                    ];
                }
            }
            $array[] = [];

            foreach ([
                'z' => 'ЗЕФИРА',
                's' => 'СУФЛЕ',
                'k' => 'КОНФЕТ'
            ] as $i => $t) {
                if ($i != 'z' && $line['type_id'] == 2 || $i == 'z') {
                    $kg = implode("+", $globalKG[$i]);
                    $boils = $line['type_id'] == 1 && $i == 'z' ? implode("+", $globalB[$i]) : '';
                    $array[] = self::makeRow([
                        1 => "ИТОГО $t",
                        4 => "<f>=" . $kg . "</f>",
                        5 => $sheet == 1 ? "<f>=" . $boils . "</f>" : '',
                        17 => "<f>=" . str_replace('E', 'R', $kg) . "</f>",
                        18 => $sheet == 1 ? "<f>=" . str_replace('F', 'S', $boils) . "</f>" : '',
                    ]);
                }
            }

            foreach ($returnMassCells as $k => $s) {
                if ($sheet == 1) {
                    $array[] = self::makeRow([
                        1 => "ИТОГО ВОЗВРАТНОЙ МАССЫ",
                        4 => "<f>=" . implode(" + ", $returnMassCells['z']) . "</f>"
                    ]);
                } else {
                    if (count($s) > 0) {
                        $title = match($k) {
                            'z' => 'ЗЕФИРНОЙ МАССЫ',
                            's' => 'СУФЛЕ',
                            'k' => 'КОНФЕТ',
                        };

                        $array[] = self::makeRow([
                            1 => "ИТОГО ВОЗВРАТНЫЕ ОТХОДЫ $title",
                            4 => "<f>=" . implode(" + ", $returnMassCells[$k]) . "</f>"
                        ]);
                    }
                }
            }

            array_push(
                $array,
                [],
                [],
                self::makeRow([1 => "<b><i>ЗАДАНИЕ СОСТАВИЛ</i></b>"]),
                self::makeRow([1 => "<b><i>ЗАДАНИЕ ПОЛУЧИЛ</i></b>"])
            );
            $arr[$sheet] = $array;
            $dateCount = 0;
        }

        // Добавляем рамки ко всем ячейкам
        foreach ($arr as $sheet => &$rows) {
            foreach ($rows as $rowIndex => &$row) {
                if (is_array($row)) {
                    foreach ($row as $colIndex => &$cell) {
                        $cell = self::styleCell((string) $cell, $colIndex, $rowIndex);
                    }
                }
            }
        }
        unset($rows, $row, $cell);

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
            ->setColWidth(12, 8)
            ->mergeCells('A4:A6')
            ->mergeCells('B4:B6')
            ->mergeCells('C4:J5')
            ->mergeCells('L4:N4')
            ->mergeCells('M5:N5')
            ->mergeCells('G6:J6')
            ->mergeCells('K5:K6')
            ->mergeCells('L5:L6')
            ->setColWidth(16, 8)
            ->setColWidth(17, 8)
            ->setColWidth(18, 8)
            ->setColWidth(19, 8)
            ->setColWidth(22, 8)
            ->setColWidth(25, 8)
            ->setColWidth(28, 20)
            ->mergeCells('T6:W6')
            ->mergeCells('V3:AA3')
            ->mergeCells('W5:X5')
            ->mergeCells('P4:Y4')
            ->mergeCells('Y5:Y6')
            ->mergeCells('AB4:AB6')
            ->mergeCells('Z5:AA5')
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
            ->setColWidth(12, 8)
            ->mergeCells('A4:A6')
            ->mergeCells('B4:B6')
            ->mergeCells('C4:J5')
            ->mergeCells('L4:N4')
            ->mergeCells('M5:N5')
            ->mergeCells('G6:J6')
            ->mergeCells('K5:K6')
            ->mergeCells('L5:L6')
            ->setColWidth(16, 8)
            ->setColWidth(17, 8)
            ->setColWidth(18, 8)
            ->setColWidth(19, 8)
            ->setColWidth(22, 8)
            ->setColWidth(25, 8)
            ->setColWidth(28, 20)
            ->mergeCells('T6:W6')
            ->mergeCells('V3:AA3')
            ->mergeCells('W5:X5')
            ->mergeCells('P4:Y4')
            ->mergeCells('Y5:Y6')
            ->mergeCells('AB4:AB6')
            ->mergeCells('Z5:AA5');

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
        $columns[8][7] = "<f>=H5-H7</f>";
        $columns[] = [''];
        $sumByLines = array_map(fn($i) => "<f>=" . implode(" + ", $i) . "</f>", $sumByLines);
        $columns[] = ["ИТОГО ПО ЗАДАНИЮ", ...$sumByLines];
        $sum = array_map(fn($i) => "<f>=" . implode(" + ", $i) . "</f>", $sum);
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
                count($amount['z']) > 0 ? "<f>=" . implode("+", $amount['z']) . "</f>" : '',
                count($amount['k']) > 0 ? "<f>=" . implode("+", $amount['k']) . "</f>" : '',
                count($amount['s']) > 0 ? "<f>=" . implode("+", $amount['s']) . "</f>" : '',
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

    private static function styleCell(string $cell, int $colIndex, int $rowIndex): string
    {
        $isFact = $colIndex >= 15 && $colIndex <= 26;
        // Строки 0-1: без границ и фона; строка 2: без границ, но с фоном для факта
        $addBorder = $rowIndex >= 3;
        $addBgcolor = $isFact && ($rowIndex >= 2);

        // Если ячейка уже содержит <style ...>, добавляем нужные атрибуты в существующий тег
        if (preg_match('/^<style\s([^>]*)>/', $cell, $m)) {
            $attrs = $m[1];
            $additions = [];
            if ($addBorder && !str_contains($attrs, 'border')) {
                $additions[] = 'border="thin"';
            }
            if ($addBgcolor && !str_contains($attrs, 'bgcolor')) {
                $additions[] = 'bgcolor="#fbcc5e"';
            }
            if ($additions) {
                $cell = preg_replace('/^<style\s/', '<style ' . implode(' ', $additions) . ' ', $cell, 1);
            }
            return $cell;
        }

        // Если стиля нет — создаём новый
        $attrs = [];
        if ($addBorder) {
            $attrs[] = 'border="thin"';
        }
        if ($addBgcolor) {
            $attrs[] = 'bgcolor="#fbcc5e"';
        }
        if ($attrs) {
            return '<style ' . implode(' ', $attrs) . '>' . $cell . '</style>';
        }
        return $cell;
    }

    private static function makeRow(array $items): array
    {
        $new = array_fill(0, 31, '');
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
