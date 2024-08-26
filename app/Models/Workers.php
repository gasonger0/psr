<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Workers extends Model
{
    protected $table = 'workers';
    protected $primary_key = 'worker_id';
    public $incrementing = true;
    protected $dateFormat = 'U';
    
    // protected function serializeDate(DateTimeInterface $date): string {
    //     return $date->format('d-m-Y H:i:s');
    // }
}
