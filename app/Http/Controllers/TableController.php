<?php

namespace App\Http\Controllers;

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
            $this->file['production'] = $xlsx->rows(0);
            $this->file['workers'] = $xlsx->rows(1);
        }

        return json_encode($this->file['workers']);

        if ($this->file['workers'] != []) {
            $this->processWorkers();
        }

        // return print_r(file_get_contents($request->files->get('file')->getRealPath()), true);
        // if ($xlsx = SimpleXLSX::parseData())

    }

    private function processWorkers()
    {

        for ($i = 0; $i < count($this->file['workers']); $i++) {
            switch ($i) {
                case 0:
                    // Шапка
                    $this->parseHeader($this->file['workers'][$i]);
                    break;
                case 1:
                    break;
                case 2:
                    break;
                default:
                    $this->parseRow($this->file['workers'][$i]);
                    break;
            }
        }
    }

    private function parseHeader($arr = []) {
        for ($k = 0; $k < count($arr); $k++) {
            if ($this->workersHeader[$arr[$k]] || $arr[$k] == "") {
                if ($this->lines[$k] != null) $this->lines[$k] = null; 
                continue;
            } else {
                if ($arr[$k] == '#REF!') {
                    $this->lines[$k] = null;
                    // Битая ссылка
                    continue;
                } else {
                    $this->lines[$k] = $arr[$k];
                    $this->lines[$k+1] = $arr[$k];
                    continue;
                }
            }
        }
        if ($this->lines != []) {
            // вопрос   $this->products_ids = ProductsController::prepareProducts($this->lines);
        }
        return;
    }

    private function parseRow($arr = []) {
        $newSlot = [];
        $slots = [];
        for ($i = 0; $i < count($arr); $i++) {
            switch ($i) {
                case 0:

                    break;
                
                case 1:

                    break;
                default:
                    # code...
                    break;
            }
            if ($this->products_ids[$this->lines[$i]]) {
                if ($i % 2 == 0) {
                    $newSlot['started_at'] = substr($arr[$i], 11, 8);
                } else {
                    $newSlot['ended_at'] = substr($arr[$i], 11, 8);
                    $slots[] = $newSlot;
                }
            }
        }
    }
}