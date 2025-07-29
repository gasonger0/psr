<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductsDictionary extends Model
{
    protected $table = 'products_dictionary';
    protected $primaryKey = 'product_id';
    public $incrementing = true;
    public $fillable = [
        'title',
        'category_id',
        'amount2parts',
        'parts2kg',
        'kg2boil',
        'cars',
        'cars2plates',
        'always_show',
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;

    public function slots(){
        return $this->hasMany(ProductsSlots::class, 'product_id');
    }

    public function category() {
        return $this->belongsTo(ProductsCategories::class,'category_id','category_id');
    }
}
