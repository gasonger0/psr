<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\Products_categories;
use App\Models\ProductsDictionary;
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
            $res = [];
            $cats = Products_categories::get(['title', 'category_id'])->toArray();
            $curCat = '';
            $rows = $xlsx->rows(0);
            foreach ($rows as $row) {
                $catMap = array_map(function($el) use($row) {
                    if (empty($row[1])) {
                        return NULL;
                    }
                    if (str_contains(strtoupper($row[1]), strtoupper($el['title']))) {
                        return $el;
                    }
                }, $cats);
                $catMap = array_filter($catMap, function($i) {return $i != NULL;});
                if(!empty($catMap)){
                    var_dump($row);
                    var_dump($catMap);
                } else if ($row[1] && !empty($row[3]) && intVal($row[3]) !== 0 && !strtotime($row[1])) {
                    $res[] = [
                        'title' => $row[1],
                        'amount' => $row[3]
                    ];
                }
            }

            $products = ProductsDictionary::get(['product_id', 'title'])->toArray();
            
            // foreach ($res as $prod) {
            //     if ($item = array_search($prod['title'], array_column($products, 'title'))) {
            //         var_dump($item);
            //     }
            // }
            return json_encode($res);
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
    public function getFile(Request $request) {
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
        foreach($lines as $line) {
           if ($line['slots'] && count($line['slots']) > 0) {
                $columns[] = [
                    '<style bgcolor="' . ($line['color'] ? $line['color'] : '#1677ff') .'">' . $line['title'] . '</style>',
                    '','','','','','',''];
                $count = count($columns);
                foreach ($line['slots'] as $slot) {
                    $worker = Workers::find($slot['worker_id']); 
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
        foreach($companies as $company) {
            $workers = array_column(
                Workers::where('company', '=', $company->company)->get(['title'])->toArray(),
                'title'
            );

            $arr = array_filter($columns, function($el) use ($workers){
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


        $xlsx = SimpleXLSXGen::fromArray( $columns );
        $name = time() . '.xlsx';
        $xlsx->saveAs($name);
        return $name;
        return $xlsx->download();
    }
    static private function getWorkTime($start, $end) {
        $start = new \DateTime($start);
        $end = new \DateTime($end);
        $diff = $start->diff($end);
        return $diff->h + ($diff->i / 60);
    }
    static private function setFloat(float $num) {
        return number_format((float) $num, 2, '.', '');
    }
    static private function summarize(array $arr, int $start, int $end) {
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