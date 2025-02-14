<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductsPlan extends Model
{
    protected $table = 'products_plan';
    protected $primaryKey = 'plan_product_id';
    public $incrementing = true;
    public $fillable = [
        'started_at',
        'ended_at'
    ];

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
