<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Lines extends Model
{
    protected $table = 'lines';
    protected $primaryKey = 'line_id';
    public $incrementing = true;
    public $fillable = [
        'title',
        'color',
        'type_id',
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

    public function linesExtra() {
        return $this->hasMany(LinesExtra::class,'line_id','line_id');
    }
    public function slots() {
        return $this->hasMany(Slots::class,'slot_id','slot_id');
    }
    public function productsSlots() {
        return $this->hasMany(ProductsSlots::class,'line_id','line_id');
    }
    public function plans()
    {
        return $this->hasManyThrough(ProductsPlan::class, ProductsSlots::class, 'product_slot_id', 'slot_id');
    }
}
