<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products_categories extends Model
{
    protected $table = 'products_categories';
    protected $primaryKey = 'category_id';
    public $incrementing = true;
}
