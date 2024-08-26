<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lines extends Model
{
    protected $table = 'lines';
    protected $primaryKey = 'line_id';
    public $incrementing = true;
    protected $dateFormat = 'U'; 
}
