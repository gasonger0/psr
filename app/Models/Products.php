<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $incrementing = true;
    // protected $dateFormat = 'U';
    public $fillable = ['started_at', 'ended_at'];


    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function addMinutes(int $minutes)
    {
        $c1 = Carbon::instance(new \DateTime($this->started_at));
        $c1->addMinutes($minutes);
        $c2 = Carbon::instance(new \DateTime($this->ended_at));
        $c2->addMinutes($minutes);
        return $this->update([
            'started_at' => $c1,
            'ended_at' => $c2
        ]);
    }

    public function slots() {
        return $this->hasMany(ProductsSlots::class, 'product_id', 'product_id');
    }
}
