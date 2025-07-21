<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductsSlots extends Model
{
    protected $table = 'products_slots';
    protected $primaryKey = 'product_slot_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'line_id',
        'people_count',
        'perfomance',
        'type_id',
        'hardware'
    ];

    public function product(){
        return $this->hasOne(ProductsDictionary::class,'product_id');
    }
    public function line(){
        return $this->hasOne(Lines::class,'line_id');
    }
    public function plans() {
        return $this->hasMany(ProductsPlan::class,'plan_product_id');
    }
}