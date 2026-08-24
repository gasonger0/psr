<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinesDefault extends Model
{
    protected $table = 'lines_defaults';
    protected $primaryKey = 'lines_default_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'line_id',
        'title',
        'perfomance',
        'started_at',
        'ended_at',
        'workers_count',
        'prep_time',
        'after_time',
    ];
}