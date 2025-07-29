<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Util;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    public function get() {
        // TODO crud надо
        return Util::successMsg(Companies::all()->toArray());
    }
}
