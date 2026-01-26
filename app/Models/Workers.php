<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Workers extends Model
{
    protected $table = 'workers';
    protected $primaryKey = 'worker_id';
    public $incrementing = true;
    public $timestamps = false;
    public $fillable = [
        'title',
        'company_id'
    ];

    public function slots() {
        return $this->hasMany(Slots::class, 'slot_id');
    }
    public function company() {
        return $this->belongsTo(Companies::class,'company_id');
    }
}
