<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCategories extends Model
{
    protected $table = 'ProductsCategories';
    protected $primaryKey = 'category_id';
    public $incrementing = true;
}
