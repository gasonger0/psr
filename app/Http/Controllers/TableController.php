<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use Illuminate\Http\Request;
use Illuminate\Queue\Worker;
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
                    if (!array_search(trim(strtolower($row[1])), self::$skipPhrases)) {
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
                    if (!array_search(trim(strtolower($row[1])), self::$skipPhrases)) {
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
                print_r($lines[$index]);
            } else {
                print_r($i);
            }
        }
        var_dump($lines);
        foreach (array_slice($this->file['workers'], 3) as $row) {
            if ($row[1] == null)
                continue;
            $worker_id = WorkersController::add($row[0], $row[1], $row[2], $row[3]);
            for ($m = 0; $m < count(array_slice($row, 4)); $m += 2) {
                if ($index = array_search([$m, $m + 1], array_column($lines, 'cells'))) {
                    if ($row[$m] == null)
                        continue;
                    var_dump($worker_id);
                    var_dump($m);
                    SlotsController::add($lines[$index]['line_id'], $worker_id, $row[$m], $row[$m + 1]);
                }
            }
        }
    }
}