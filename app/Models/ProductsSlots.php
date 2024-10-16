<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductsSlots extends Model
{
    protected $table = 'products_slots';
    protected $primaryKey = 'product_slot_id';
    public $incrementing = true;

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}