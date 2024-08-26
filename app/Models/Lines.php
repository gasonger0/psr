<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Lines extends Model
{
    protected $table = 'lines';
    protected $primaryKey = 'line_id';
    public $incrementing = true;
    protected $dateFormat = 'U';

    // protected function serializeDate(DateTimeInterface $date): string {
    //     return $date->format('d-m-Y H:i:s');
    // }
}
