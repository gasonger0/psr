<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCategories extends Model
{
    protected $table = 'products_categories';
    protected $primaryKey = 'category_id';
    public $incrementing = true;
    public $timestamps = false;
    public $fillable = [
        'title',
        'parent'
    ];

    public function products() {
        return $this->hasMany(ProductsDictionary::class, 'category_id', 'category_id');
    }

    public function children() 
    {
        return $this->hasMany(ProductsCategories::class, 'parent');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}
