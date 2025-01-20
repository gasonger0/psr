<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Logs extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'log_id';
    public $incrementing = true;

    
    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
