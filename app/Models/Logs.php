<?php

namespace App\Models;

use App\withSession;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Logs extends Model
{
    use withSession;
    protected $table = 'logs';
    protected $primaryKey = 'log_id';
    public $incrementing = true;
    public $timestamps = false;
    public $fillable = [
        'action',
        'people_count',
        'line_id',
        'workers',
        'date',
        'isDay',
        'started_at',
        'ended_at'
    ];
}
