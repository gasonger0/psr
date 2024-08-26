<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slots extends Model
{
    protected $table = 'slots';
    protected $primaryKey = 'slot_id';
    public $incrementing = true;
    protected $dateFormat = 'U';
}
