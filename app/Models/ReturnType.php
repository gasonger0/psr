<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnType extends Model
{
    protected $table = 'return_types';

    protected $primaryKey = 'return_type_id';

    public $timestamps = false;

    protected $fillable = ['return_type_id', 'title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k'];
}
