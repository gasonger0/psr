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

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function product() {
        return $this->hasOne(ProductsDictionary::class,'product_id');
    }
}
