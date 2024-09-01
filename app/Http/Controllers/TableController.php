<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSX;

class TableController extends Controller
{
    private $file = [];

    private $workersHeader = [
        'Организация' => 0,
        'СПИСОК РАБОЧИХ' => 1,
        'Обед' => 2
    ];

    private $lines = [];
    private $products_ids =[];
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

        return json_encode($this->file['workers']);

        if ($this->file['workers'] != []) {
            $this->processProducts();
        }

        if ($this->file['products'] != []) {
            $this->processWorkers();
        }

    }

    private function processProducts() {
        foreach ($this->file['prosucts'] as $row) {
            if ($row[1] != null &&
                $row[2] != null &&
                $row[3] != null &&
                $row[4] != null) {
                    if ($row[0] == null) {
                        // Строка линии
                        LinesController::add(
                            $row[1], 
                            $row[2], 
                            $row[3], 
                            $row[4]
                        );
                    } else {
                        // Строка продукта
                        ProductsController::add(
                            $row[0],
                            $row[1], 
                            $row[2], 
                            $row[3], 
                            $row[4]
                        );
                    }
                } else {
                    continue;
                }
        }
    } 

    private function processWorkers() {
        $lines = json_decode(LinesController::getList(['line_id', 'title']), true);
        $lineCells = array_slice($this->file['workers'][0], 4);
        for ($i = 0; $i < count($lineCells); $i+=2) {
            if (!empty($lineCell[$i]) && $index = array_search(array_column($lines, 'title'), $lineCells[$i])) {
                $lines[$index]["cells"] = [$i, $i+1]; 
            }
        }
        foreach (array_slice($this->file['workers'], 4) as $row) {
            WorkersController::add($row[0], $row[1], $row[2], $row[3]);
            ///!!!! Обработать ячейки
        }
    }
}