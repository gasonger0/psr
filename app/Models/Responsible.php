<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Responsible extends Model
{
    protected $table = 'responsible';
    protected $primaryKey = 'responsible_id';
    public $incrementing = true;    

    public $fillable = [
        'title',
        'position',
        'created_at',
        'updated_at'
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
