<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Companies extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'company_id';
    public $incrementing = true;
    public $fillable = [
        'title'
    ];
    public $timestamps = false;

    public function workers() {
        return $this->hasMany(Workers::class, 'company', 'company_id');
    }
}
