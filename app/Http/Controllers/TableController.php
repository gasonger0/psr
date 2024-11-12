<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\Products_categories;
use App\Models\ProductsDictionary;
use App\Models\ProductsOrder;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\Responsible;
use App\Models\Slots;
use App\Models\Workers;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class TableController extends Controller
{
    private $file = [];

    private static $skipPhrases = ['подготовительное время', 'заключительное время'];

    public function loadFile(Request $request)
    {
        if (!$request->files) {
            return 'Файл не предоставлен';
        }

        if ($xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath())) {
            $this->file['products'] = $xlsx->rows(0);
            $this->file['workers'] = $xlsx->rows(1);
        }

        LinesController::dropData();
        ProductsController::dropData();
        WorkersController::dropData();
        SlotsController::dropData();

        if ($this->file['products'] != []) {
            $this->processProducts();
        }

        if ($this->file['workers'] != []) {
            $this->processWorkers();
        }

        return 0;
    }
    public function loadOrder(Request $request)
    {
        if (!$request->files) {
            return 'Файл не предоставлен';
        }

        if ($xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath())) {
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
                        array_column($products,  'title')
                    );
                    if ($product_index !== false) {
                        $amounts[] = [
                            'product_id' => $products[$product_index]['product_id'],
                            'amount'     => $row[3]
                        ];
                        continue;
                    } else if ($row[0] != null) {
                        $prod = new ProductsDictionary();
                        $prod->title = $row[1];
                        $prod->category_id = $curCat['category_id'];
                        $prod->save();
                        $amounts[] = [
                            'product_id' => $prod->product_id,
                            'amount'     => $row[3]
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
                    $am = ProductsOrder::where('product_id', '=', $amount['product_id'])->get();
                    if ($am->count() > 0) {
                        foreach ($am as $i) {
                            $i->amount = $val;
                            $i->save();
                        }
                    } else {
                        $order = new ProductsOrder();
                        $order->product_id = $amount['product_id'];
                        $order->amount = $val;
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

            $categories = array_map(function($el){
                $el['title'] = mb_strtoupper($el['title']);
                return $el;
            }, $categories);
            // $products = ProductsDictionary::all(['product_id', 'title'])->toArray();
            // ProductsDictionaryController::clear();
            // ProductsSlotsController::clear();

            $activeCategory = null;
            $activeProduct = null;
            foreach ($xlsx->rows() as $k => $row) {
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
                    } else {
                        var_dump($row[1]);
                    }
                    continue;
                } 

                // Slots
                $line_id = Lines::where('title', '=', $row[4])
                    ->first()->line_id ?? null;
                $boil = ProductsSlots::where('product_id', '=', $activeProduct)
                    ->where('line_id', '=', $line_id)
                    ->first();
                if (!$boil) {
                    $boil = new ProductsSlots();
                }
                $boil->product_id = $activeProduct;
                $boil->line_id = $line_id;
                $boil->people_count = intval($row[7]) ?? 0;
                $boil->perfomance = $row[5] ?? 0;
                $boil->type_id = 1;
                $boil->save();

                $pack = array_slice($row, 15, 4 * 5);
                $pack = array_chunk($pack, 4);
                return;
            }
        }
    }

    public function loadFormulas(Request $request)
    {
        $xlsx = SimpleXLSX::parse($request->files->get('file')->getRealPath());
        $arr = [];
        $dataIndexes = [
            1 => 'title',
            3 => 'crates to parts',
            4 => 'parts to kg',
            5 => 'kg to vark',
            6 => 'TG',
            8 => '.tg'
        ];

        $fieldsTitles = [
            1 => 'title',
            2 => 'amount',
            3 => 'parts',
            4 => 'kg',
            5 => 'boiling',
            6 => 'cars'
        ];

        foreach ($xlsx->rowsEx() as $k => $row) {
            $formulas =
                array_filter(
                    array_map(function ($m) {
                        return isset($m['f']) ? $m['f'] : $m['value'];
                    }, array_slice($row, 0, 9)),
                    function ($i) {
                        return $i != null;
                    }
                );
            if (count($formulas) > 0) {
                foreach ($formulas as $i => $j) {
                    $letter = chr(97 + $i);
                }
            }
            $arr[$k] = $formulas;
        }

        return $arr;
    }

    public function dowloadForPrint()
    {
        $plans = json_decode(ProductsPlanController::getList(new Request()), true);

        $linesFromPlans = array_unique(array_map(function ($el) {
            return $el['line_id'];
        }, $plans));
        $productsFromLines = array_unique(array_map(function ($el) {
            return $el['product_id'];
        }, $plans));

        $r = json_decode(ResponsibleController::getList(), true);
        $responsibles = [];
        foreach ($r as $f) {
            $responsibles[$f['responsible_id']] = $f['name'];
        }

        $lines = Lines::whereIn('line_id', $linesFromPlans)->get(['line_id', 'title', 'started_at', 'ended_at', 'master', 'engineer'])->toArray();
        $products = ProductsDictionary::whereIn('product_id', $productsFromLines)->get(['product_id', 'title', 'amount2parts', 'parts2kg', 'kg2boil', 'cars'])->toArray();

        $array = [[
            '№',
            'Наименование',
            'Плановое кол-во корпуса',
            '',
            '',
            '',
            '',
            '',
            '',
            'План',
            ''
        ], [
            '',
            '',
            'ящ',
            'шт',
            'кг',
            'варка',
            'телеги',
            '',
            '',
            '',
            '',
            'кол-во людей',
            'начало',
            'окончание'
        ]];

        foreach ($lines as &$line) {
            $linePlans = array_filter($plans, function ($el) use ($line) {
                return $el['line_id'] == $line['line_id'];
            });

            $linePlans = array_map(function ($el) use ($products) {
                $title = array_search(
                    $el['product_id'],
                    array_column($products, 'product_id')
                );

                if ($title !== false) {
                    $el['title'] = $products[$title]['title'];
                    $el['amount2parts'] = $products[$title]['amount2parts'];
                    $el['parts2kg'] = $products[$title]['parts2kg'];
                    $el['kg2boil'] = $products[$title]['kg2boil'];
                    $el['cars'] = $products[$title]['cars'];
                }

                return $el;
            }, $linePlans);

            $line['items'] = $linePlans;

            $line['master'] = $line['master'] ? $responsibles[$line['master']] : '';
            $line['engineer'] = $line['engineer'] ? $responsibles[$line['engineer']] : '';

            $array[] = ['', 'Ответственные:' . $line['master'] . ',' . $line['engineer']];
            $array[] = ['', $line['title']];

            var_dump($line);
            foreach ($line['items'] as $product) {
                if (!isset($product['amount2parts'])) {
                    continue;
                }
                $crates = floatval($product['amount']);
                $parts = eval('return ' . $crates . '*' . floatval($product['amount2parts']) . ';');
                $kg = eval('return ' . $parts . '*' . floatval($product['parts2kg']) . ';');
                $boils = eval('return ' . $kg . '*' . floatval($product['kg2boil']) . ';');
                $cars = eval('return ' . $boils . '*' . floatval($product['cars']) . ';');
                $array[] = [
                    '',
                    $product['title'],
                    $crates,
                    $parts,
                    $kg,
                    $boils,
                    ceil($cars)
                ];
                $array[] = [];
            }
        }


        $xlsx = SimpleXLSXGen::fromArray($array);
        $name = 'План_' . date('d_m_Y-H:i:s', time()) . '.xlsx';
        $xlsx->saveAs($name);
        return $name;
    }

    public function loadPlan(Request $request)
    {
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
                $i->amount = $item['amount'];
                $i->save();
            }
        }
        if ($data['lines']) {
            foreach ($data['lines'] as $item) {
                $i = Lines::find($item['line_id']);
                if ($i) {
                    $i->master = $item['master'];
                    $i->started_at = $item['started_at'];
                    $i->ended_at = $item['ended_at'];
                    $i->color = $item['color'];
                    $i->type_id = $item['type_id'];
                    $i->engineer = $item['engineer'];
                    $i->save();
                }
            }
        }
    }

    private function processProducts()
    {
        $currentLine = null;
        foreach (array_slice($this->file['products'], 4) as $row) {
            if (
                $row[1] != null &&
                $row[2] != null &&
                $row[3] != null &&
                $row[4] != null
            ) {
                if ($row[0] == null) {
                    // Строка линии
                    if (array_search(trim(strtolower($row[1])), self::$skipPhrases) === false) {
                        $currentLine = LinesController::add(
                            trim($row[1]),
                            trim($row[2]),
                            trim($row[3]),
                            trim($row[4])
                        );
                    } else {
                        continue;
                    }
                } else {
                    // Строка продукта
                    if (array_search(trim(strtolower($row[1])), self::$skipPhrases) === false) {
                        ProductsController::add(
                            trim($row[0]),
                            $currentLine,
                            trim($row[1]),
                            trim($row[2]),
                            trim($row[3]),
                            trim($row[4])
                        );
                    } else {
                        continue;
                    }
                }
            } else {
                continue;
            }
        }
    }
    private function processWorkers()
    {
        $lines = json_decode(LinesController::getList(['line_id', 'title']), true);
        $lineCells = array_slice($this->file['workers'][0], 4);
        for ($i = 0; $i < count($lineCells); $i += 2) {
            if (!empty($lineCells[$i]) && ($index = array_search($lineCells[$i], array_column($lines, 'title'))) !== false) {
                $lines[$index]["cells"] = [$i, $i + 1];
            }
        }
        foreach (array_slice($this->file['workers'], 3) as $row) {
            if ($row[1] == null)
                continue;
            $worker_id = WorkersController::add($row[0], $row[1], $row[2], $row[3]);
            $row = array_slice($row, 4);
            // $time = 0;      // time in minutes
            for ($m = 0; $m < count($row); $m += 2) {
                if (($index = array_search([$m, $m + 1], array_column($lines, 'cells'))) !== false) {
                    if ($row[$m] == null)
                        continue;
                    SlotsController::add($lines[$index]['line_id'], $worker_id, $row[$m], $row[$m + 1]);
                    // $bufdiff = (new \DateTime($row[$m]))->diff(new \DateTime($row[$m+1]));
                    //$time += $bufdiff->h * 60 + $bufdiff->i;
                    // WorkersController::updateBaseTime($worker_id, $time);
                    // $time = 0;
                }
            }
        }
    }
    public function getFile(Request $request)
    {
        $data  = $request->post();

        $lines = Lines::all();
        foreach ($lines as $line) {
            $line['slots'] = Slots::where('line_id', '=', $line['line_id'])->get();
        }

        $columns = [
            ['<b><i>Наряд за</i></b>', date('d.m.Y H.i.s', time()), '', '', '', '', '', ''],
            array_fill(0, 8, ''),
            [
                'Список рабочих',
                'Отработано часов по плану',
                'Отработано часов по факту',
                'в т.ч. Простои',
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
                foreach ($line['slots'] as $slot) {
                    $worker = Workers::find($slot['worker_id']);
                    /**
                     * Обработать все слоты, на которых работал человек, а не каждый отдельно
                     */
                    $workTime = self::setFloat(self::getWorkTime($slot['started_at'], $slot['ended_at']));
                    $ktu = $data[array_search($slot['worker_id'], array_column($data, 'worker_id'))]['ktu'];
                    $columns[] = [
                        $worker['title'],
                        self::setFloat($slot['time_planned'] / 60),
                        $workTime,
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
                    '<style bgcolor="#FDE9D9">' . self::summarize(array_column($columns, 2), $count, $count1) . '<style bgcolor="#FDE9D9">',
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
                array_sum(array_column($arr, 2)),
                array_sum(array_column($arr, 3)),
                array_sum(array_column($arr, 4)),
                array_sum(array_column($arr, 5)),
                array_sum(array_column($arr, 6))
            ];
        }


        $xlsx = SimpleXLSXGen::fromArray($columns);
        $name = 'Отчёт_' . date('d_m_Y-H:i:s', time()) . '.xlsx';
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
            $result += $arr[$i];
        }
        return $result;
    }
}
