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
        'product_id',
        'line_id',
        'slot_id',
        'workers_count',
        'type_id',
        'started_at',
        'ended_at',
        'amount',
        'date',
        'isDay',
        'hardware',
        'position'
    ];

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public static function joinProductTitle(Builder $query){
        return $query->join('products_dictionary', 'products_plan.product_id', '=', 'products_dictionary.product_id')
            ->select('products_plan.*', DB::raw('products_dictionary.title as title'));
    }
}
