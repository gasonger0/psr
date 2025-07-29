<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCategories extends Model
{
    protected $table = 'products_categories';
    protected $primaryKey = 'category_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'title',
        'parent'
    ];

    public function products() {
        return $this->hasMany(ProductsDictionary::class, 'category_id', 'category_id');
    }
}
