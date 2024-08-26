<?php

use App\Http\Controllers\LinesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Lines
 */

Route::get('/get_lines', [LinesController::class, 'getList']);


