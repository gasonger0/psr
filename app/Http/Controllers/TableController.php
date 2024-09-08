<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\Slots;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;

class TableController extends Controller
{
    private $file = [];

    private static $skipPhrases = ['подготовительное время', 'заключительное время'];

    private $lines = [];
    private $products_ids = [];
    private $msg = '';

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
                var_dump(array_search(trim(strtolower($row[1])), self::$skipPhrases));
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
        var_dump($lines);
        // die();
        foreach (array_slice($this->file['workers'], 3) as $row) {
            if ($row[1] == null)
                continue;
            $worker_id = WorkersController::add($row[0], $row[1], $row[2], $row[3]);
            $row = array_slice($row, 4);
            for ($m = 0; $m < count($row); $m += 2) {
                if (($index = array_search([$m, $m + 1], array_column($lines, 'cells'))) !== false) {
                    if ($row[$m] == null)
                        continue;
                    print_r("index: " . $index . PHP_EOL);
                    print_r($m . ' ' . $m+1 . PHP_EOL);
                    print_r("line: " . $lines[$index]['title'] . PHP_EOL);
                    print_r("row: " . $row[$m] . ' ' . $row[$m + 1] . PHP_EOL . PHP_EOL);
                    SlotsController::add($lines[$index]['line_id'], $worker_id, $row[$m], $row[$m + 1]);
                }
            }
        }
    }
    public function getFile(Request $request) {
        $data  = $request->post();
        $columns = [
            ['<b><i>Наряд за</i></b>', '', date('d.m.Y H.i.s', time())]];
    
        $slots = Slots::where(['worker_id' => $data['worker_id']])->get();

        $lines = Lines::all(['line_id', 'title']);
        
        foreach ($lines as $line) {
            /**
             * @todo Поменять модели на контроллеры и дописать отчётность
             */
        }
    }
}