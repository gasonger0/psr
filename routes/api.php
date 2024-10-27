<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\ProductsCategoriesController;
use App\Http\Controllers\ProductsDictionaryController;
use App\Http\Controllers\ProductsOrderController;
use App\Http\Controllers\ProductsPlanController;
use App\Http\Controllers\ProductsSlotsController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WorkersController;
use App\Models\ProductsPlan;
use Illuminate\Support\Facades\Route;

/*******
 * LINES
 ******/
Route::get( '/get_lines',           [LinesController::class,                'getList'   ]); 
Route::post('/save_line',           [LinesController::class,                'save'      ]);
Route::post('/down_line',           [LinesController::class,                'down'      ]);

/*********
 * WORKERS
 ********/
Route::get( '/get_workers',         [WorkersController::class,              'getList'   ]);
Route::post('/save_worker',         [WorkersController::class,              'save'      ]);
Route::post('/change_lines',        [WorkersController::class,              'change'    ]);

/*******
 * SLOTS
 ******/
Route::get( '/get_slots',           [SlotsController::class,                'getList'   ]);
Route::post('/change_slot',         [SlotsController::class,                'change'    ]);
Route::post('/edit_slot',           [SlotsController::class,                'edit'      ]);
Route::post('/delete_slot',         [SlotsController::class,                'delete'    ]);
Route::post('/replace_worker',      [SlotsController::class,                'replace'   ]);

/*********************
 * PRODUCTS_DICTIONARY
 ********************/
Route::post('/get_products',        [ProductsDictionaryController::class,   'getList'   ]);
Route::post('/add_products',        [ProductsDictionaryController::class,   'addProduct']);

/*********************
 * PRODUCTS_CATEGORIES
 ********************/
Route::get('/get_categories',       [ProductsCategoriesController::class,   'getTree'   ]);


/****************
 * PRODUCTS_PLANS
 ***************/
Route::get('/get_product_plans',    [ProductsPlanController::class,         'getList'   ]);
Route::post('/add_product_plan',    [ProductsPlanController::class,         'addPlan'   ]);
Route::post('/delete_product_plan', [ProductsPlanController::class,         'delPlan'   ]);
Route::delete('/clear_plan',        [ProductsPlanController::class,         'clear'     ]);

/****************
 * PRODUCTS_SLOTS
 ***************/
Route::post('/get_product_slots',   [ProductsSlotsController::class,        'getList'   ]);
Route::post('add_product_slots',    [ProductsSlotsController::class,        'addSlots'  ]);

/*****************
 * PRODUCTS_ORDERS
 ****************/
Route::get('/get_product_orders',   [ProductsOrderController::class,        'getList'   ]);

/******
 * XLSX
 *****/

Route::post('/load_xlsx',           [TableController::class,                'loadFile'  ]);
Route::post('/load_order',          [TableController::class,                'loadOrder'  ]);
Route::post('/get_xlsx' ,           [TableController::class,                'getFile'   ]);

/******
 * LOGS
 *****/
Route::get('/get_logs',             [LogsController::class,                 'getAll'    ]);
Route::post('/add_log',             [LogsController::class,                 'add'       ]);
