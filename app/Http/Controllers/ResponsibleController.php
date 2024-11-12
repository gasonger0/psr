<?php

namespace App\Http\Controllers;

use App\Models\Responsible;
use Illuminate\Http\Request;

class ResponsibleController extends Controller
{
    public static function getList()
    {
        return Responsible::all()->toJson();
    }
}
