<?php

namespace App\Http\Controllers;

use App\Models\Workers;
use Illuminate\Http\Request;

class WorkersController extends Controller
{
    public function getList() {
        return Workers::all()->toJson();
    }
}
