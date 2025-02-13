<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class LinesExtra extends Model
{
    protected $table = 'line_extra';
    protected $primaryKey = 'line_extra_id';
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
}
