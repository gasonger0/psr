<?php

namespace App\Models;

use App\withSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;

class ProductsPlan extends Model
{
    use withSession;
    protected $table = 'products_plan';
    protected $primaryKey = 'plan_product_id';
    public $incrementing = true;
    public $fillable = [
        'started_at',
        'ended_at',
        'slot_id',
        'amount',
        'date',
        'isDay',
        'delay',
        'colon',
        'parent'
    ];
    public $timestamps = false;

    public function slot()
    {
        return $this->hasOne(ProductsSlots::class, 'product_slot_id', 'slot_id');
    }

    public function line() {
        return $this->hasManyThrough(Lines::class, ProductsSlots::class,'plan_product_id','slot_id');
    }
}
