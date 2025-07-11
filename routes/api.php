<?php

use App\Http\Controllers\LinesController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\ProductsCategoriesController;
use App\Http\Controllers\ProductsDictionaryController;
use App\Http\Controllers\ProductsOrderController;
use App\Http\Controllers\ProductsPlanController;
use App\Http\Controllers\ProductsSlotsController;
use App\Http\Controllers\ResponsibleController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WorkersController;
use App\Http\Controllers\LinesExtraController;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ParseSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::group(['middleware' => ['web', ParseSession::class]], function () {

    Route::controller(LinesController::class)
        ->prefix('/lines')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/add', 'add');
            Route::delete('/delete', 'delete');
            Route::put('/down', 'down');
        });

    /*******
     * LINES
     ******/
    // Route::get('/get_lines', [LinesController::class, 'getList']);
    // Route::post('/save_line', [LinesController::class, 'save']);
    // Route::post('/down_line', [LinesExtraController::class, 'down']);
    // Route::post('/delete_line', [LinesController::class, 'delete']);

    /*********
     * WORKERS
     ********/
    Route::get('/get_workers', [WorkersController::class, 'getList']);
    Route::post('/save_worker', [WorkersController::class, 'save']);
    Route::post('/change_lines', [WorkersController::class, 'change']);
    Route::post('/add_worker', [WorkersController::class, 'addFromWeb']);
    Route::post('/edit_workers', [WorkersController::class, 'edit']);

    /*******
     * SLOTS
     ******/
    Route::controller(SlotsController::class)
        ->prefix('/workers_slots')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('get', 'get');
        });
    Route::get('/get_slots', [SlotsController::class, 'getList']);
    Route::post('/change_slot', [SlotsController::class, 'change']);
    Route::post('/edit_slot', [SlotsController::class, 'edit']);
    Route::post('/delete_slot', [SlotsController::class, 'delete']);
    Route::post('/replace_worker', [SlotsController::class, 'replace']);
    Route::get('/print_slots', [SlotsController::class, 'print']);

    /*********************
     * PRODUCTS_DICTIONARY
     ********************/
    Route::post('/get_products', [ProductsDictionaryController::class, 'getList']);
    Route::post('/save_product', [ProductsDictionaryController::class, 'saveProduct']);
    Route::post('/delete_product', [ProductsDictionaryController::class, 'deleteProduct']);

    /*********************
     * PRODUCTS_CATEGORIES
     ********************/
    Route::get('/get_categories', [ProductsCategoriesController::class, 'getTree']);


    /****************
     * PRODUCTS_PLANS
     ***************/
    Route::controller(ProductsPlanController::class)
        ->prefix('/plans')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::put('/change', 'change');
            Route::post('/add', 'add');
            Route::delete('/delete', 'delete');
            Route::delete('/clear', 'clear');
        });
    Route::get('/get_product_plans', [ProductsPlanController::class, 'getList']);
    Route::post('/add_product_plan', [ProductsPlanController::class, 'add']);
    Route::post('/change_plan', [ProductsPlanController::class, 'changePlan']);
    Route::post('/delete_product_plan', [ProductsPlanController::class, 'delPlan']);
    Route::delete('/clear_plan', [ProductsPlanController::class, 'clear']);

    /****************
     * PRODUCTS_SLOTS
     ***************/
    Route::post('/get_product_slots', [ProductsSlotsController::class, 'getList']);
    Route::post('/add_product_slots', [ProductsSlotsController::class, 'addSlots']);
    Route::post('/delete_product_slot', [ProductsSlotsController::class, 'delete']);

    /*****************
     * PRODUCTS_ORDERS
     ****************/
    Route::get('/get_product_orders', [ProductsOrderController::class, 'getList']);

    /*************
     * RESPONSIBLE
     ************/
    Route::get('/get_responsible', [ResponsibleController::class, 'getList']);
    Route::post('/edit_responsible', [ResponsibleController::class, 'edit']);

    /******
     * XLSX
     *****/

    Route::post('/load_xlsx', [TableController::class, 'loadFile']);
    Route::post('/load_order', [TableController::class, 'loadOrder']);
    Route::post('/load_defaults', [TableController::class, 'loadDefaults']);
    Route::post('/get_xlsx', [TableController::class, 'getFile']);
    Route::post('/load_plan_json', [TableController::class, 'loadPlan']);
    Route::get('/download_plan', [TableController::class, 'dowloadForPrint']);
    Route::post('/load_formulas', [TableController::class, 'loadFormulas']);
    Route::get('/download_json_plan', [TableController::class, 'downloadPlanJson']);
    Route::get('/get_plans', [TableController::class, 'getPlans']);

    /******
     * LOGS
     *****/
    Route::get('/get_logs', [LogsController::class, 'getAll']);
    Route::post('/add_log', [LogsController::class, 'add']);
    Route::get('/load_logs', [LogsController::class, 'logXlsx']);


    /********
     * SESSION
     *******/
    Route::post('/update_session', function (Request $request) {
        if ($request) {
            $dateValue = $request->post('date');
            $timeValue = $request->post('isDay');
        } else {
            $dateValue = $request->cookie('date');
            $timeValue = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        }
        $response = response('Set Cookie');
        $response->cookie('date', $dateValue, 60);
        $response->cookie('isDay', $timeValue, 60);
        return $response;

    });
});