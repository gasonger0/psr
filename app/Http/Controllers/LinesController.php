<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use Illuminate\Http\Request;

class LinesController extends Controller
{
    public function getList() {
        return Lines::all()->toJson();
    }

}
