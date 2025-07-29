<?php

namespace App\Models;

use App\withSession;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductsOrder extends Model
{
    use withSession;
    protected $table = 'products_order';
    protected $primaryKey = 'order_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'amount',
        'isDay',
        'date'
    ];
    
    public function product() {
        return $this->hasOne(ProductsDictionary::class,'product_id');
    }
}
