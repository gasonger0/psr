<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Workers extends Model
{
    protected $table = 'workers';
    protected $primaryKey = 'worker_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'title',
        'company'
    ];

    public function slots() {
        return $this->hasMany(Slots::class, 'slot_id');
    }

    // public function addMinutes(int $minutes) {
    //     $c1 = Carbon::instance(new \DateTime($this->time_planned));
    //     $c1->addMinutes($minutes);
    //     return $this->update([
    //         'time_planned' => $c1
    //     ]);
    // }
}
