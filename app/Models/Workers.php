<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Workers extends Model
{
    protected $table = 'workers';
    protected $primaryKey = 'worker_id';
    public $incrementing = true;
    // protected $dateFormat = 'U';
    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
        'company'
    ];
    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    // public function addMinutes(int $minutes) {
    //     $c1 = Carbon::instance(new \DateTime($this->time_planned));
    //     $c1->addMinutes($minutes);
    //     return $this->update([
    //         'time_planned' => $c1
    //     ]);
    // }
}
