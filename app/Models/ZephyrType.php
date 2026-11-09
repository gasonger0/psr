<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZephyrType extends Model
{
    protected $table = 'zephyr_types';

    protected $primaryKey = 'zephyr_type_id';

    public $timestamps = false;

    protected $fillable = ['zephyr_type_id', 'title', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];
}
