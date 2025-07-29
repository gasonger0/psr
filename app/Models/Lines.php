<?php

namespace App\Models;
use App\withSession;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Lines extends Model
{
    use withSession;
    protected $table = 'lines';
    protected $primaryKey = 'line_id';
    public $incrementing = true;
    public $fillable = [
        'title',
        'color',
        'type_id'
    ];

    public $timestamps = false;

    public function linesExtra() {
        return $this->hasMany(LinesExtra::class,'line_id','line_id');
    }
    public function slots() {
        return $this->hasMany(Slots::class,'slot_id','slot_id');
    }
    public function prodSlots() {
        return $this->hasMany(ProductsSlots::class,'slot_id','product_slot_id');
    }
    public function productsSlots() {
        return $this->hasMany(ProductsSlots::class,'line_id','line_id');
    }
    public function plans() {
        return $this->hasManyThrough(ProductsPlan::class, ProductsSlots::class, 'line_id', 'slot_id');
    }
    
}
