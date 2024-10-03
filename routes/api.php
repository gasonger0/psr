<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WorkersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*******
 * LINES
 ******/
Route::get( '/get_lines',       [LinesController::class,    'getList'   ]); 
Route::post('/save_line',       [LinesController::class,    'save'      ]);
Route::post('/down_line',       [LinesController::class,    'down'      ]);

/*********
 * WORKERS
 ********/
Route::get( '/get_workers',     [WorkersController::class,  'getList'   ]);
Route::post('/save_worker',     [WorkersController::class,  'save'      ]);
Route::post('/change_lines',    [WorkersController::class,  'change'    ]);

/*******
 * SLOTS
 ******/
Route::get( '/get_slots',       [SlotsController::class,    'getList'   ]);
Route::post('/change_slot',     [SlotsController::class,    'change'    ]);
Route::post('/edit_slot',       [SlotsController::class,    'edit'      ]);
Route::post('/delete_slot',     [SlotsController::class,    'delete'    ]);
Route::post('/replace_worker',  [SlotsController::class,    'replace'   ]);

/******
 * XLSX
 *****/

Route::post('/load_xlsx',       [TableController::class,    'loadFile'   ]);
Route::post('/get_xlsx' ,       [TableController::class,    'getFile'    ]);

/******
 * LOGS
 *****/
Route::get('/get_logs',         [LogsController::class,     'getAll'     ]);
Route::post('/add_log',         [LogsController::class,     'add'        ]);
