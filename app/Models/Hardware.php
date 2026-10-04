<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hardware extends Model
{
    protected $table = 'hardwares';

    protected $primaryKey = 'hardware_id';

    public $timestamps = false;

    protected $fillable = ['hardware_id', 'title', 'full_title', 'type'];
}
