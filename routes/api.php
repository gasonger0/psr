<?php

use App\Http\Controllers\CompaniesController;
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
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ParseSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

Route::group(['middleware' => ['web', ParseSession::class]], function () {
    /*******
     * LINES
     ******/
    Route::controller(LinesController::class)
        ->prefix('/lines')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            // crud
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/create', 'create');
            Route::delete('/delete', 'delete');
            // actions
            Route::put('/down', 'down');
        });

    /*********
     * WORKERS
     ********/
    Route::controller(WorkersController::class)
        ->prefix('/workers')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            // crud
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/create', 'create');
            Route::delete('/delete', 'delete');
        });

    /*******
     * SLOTS
     ******/
    Route::controller(SlotsController::class)
        ->prefix('/workers_slots')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            // crud
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/create', 'create');
            Route::delete('/delete', 'delete');
            // actions
            Route::put('/change', 'change');
            Route::put('/replace', 'replace');
        });
    Route::get('/workers_slots/print', [SlotsController::class, 'print']);

    /***********
     * COMPANIES
     **********/
    Route::controller(CompaniesController::class)
        ->prefix('/companies')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            // TODO crud!
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/create', 'create');
            Route::delete('/delete', 'delete');
        });


    /*********************
     * PRODUCTS_DICTIONARY
     ********************/
    Route::controller(ProductsDictionaryController::class)
        ->prefix('/products')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::post('/get', 'get');
            Route::post('/create', 'create');
            Route::put('/update', 'update');
            Route::delete('/delete', 'delete');
        });
    Route::post('/get_products', [ProductsDictionaryController::class, 'getList']);

    /*********************
     * ProductsCategories
     ********************/

    // TODO CRUD
    Route::controller(ProductsCategoriesController::class)
        ->prefix('/categories')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/get', 'get');
            Route::get('/get_tree', 'getTree');
        });


    /****************
     * PRODUCTS_PLANS
     ***************/
    Route::controller(ProductsPlanController::class)
        ->prefix('/plans')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            // crud
            Route::get('/get', 'get');
            Route::put('/update', 'update');
            Route::post('/create', 'create');
            Route::delete('/delete', 'delete');
            // actions
            Route::delete('/clear', 'clear');
            Route::put('/change', 'change');
        });

    /****************
     * PRODUCTS_SLOTS
     ***************/
    Route::controller(ProductsSlotsController::class)
        ->prefix('/products_slots')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/get', 'get');
            Route::post('/create', 'create');
            Route::put('/update', 'update');
            Route::delete('/delete', 'delete');
        });

    /*************
     * RESPONSIBLE
     ************/
    Route::controller(ResponsibleController::class)
        ->prefix('/responsibles')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/get', 'get');
            Route::post('/create', 'create');
            Route::put('/update', 'update');
            Route::delete('/delete', 'delete');
        });

    /******
     * XLSX
     *****/
    Route::controller(TableController::class)
        ->prefix('/tables')
        ->group(function () {
            Route::middleware(ForceJsonResponse::class)->post('/load_order', 'loadOrder');
            Route::get('/get_plans', 'getPlans');
        });


    Route::post('/load_xlsx', [TableController::class, 'loadFile']);
    // Route::post('/load_order', [TableController::class, 'loadOrder']);
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
        Route::controller(LogsController::class)
        ->prefix('/logs')
        ->group(function () {
            Route::get('/get', 'get')->middleware(ForceJsonResponse::class);
            Route::get('/load', 'print');
        });


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
        $response->cookie('date', $dateValue, 60000);
        $response->cookie('isDay', $timeValue, 60000);
        return $response;

    });
});