<?php

namespace App\Http\Controllers;

use App\Models\Slots;
use Illuminate\Http\Request;

class SlotsController extends Controller
{
    public function getList() {
        return Slots::all()->toJson();
    }
}
