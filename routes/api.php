<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WorkersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * GET LISTS
 */
Route::get('/get_lines',    [LinesController::class,    'getList']);
Route::get('/get_workers',  [WorkersController::class,  'getList']);
Route::get('/get_slots',    [SlotsController::class,    'getList']);


/**
 * XLSX
 */

 Route::post('/load_xlsx',  [TableController::class,    'loadFile']);
