<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsOrder extends Model
{
    protected $table = 'products_order';
    protected $primaryKey = 'order_id';
    public $incrementing = true;
}
