<?php

namespace App\Models;

use App\withSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Slots extends Model
{
    protected $table = 'slots';
    protected $primaryKey = 'slot_id';
    public $incrementing = true;
    // protected $dateFormat = 'U';
    protected $fillable = ['started_at', 'ended_at', 'workers_count'];
    use withSession;

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function addMinutes(int $minutes) {
        $c1 = Carbon::instance(new \DateTime($this->started_at));
        $c1->addMinutes($minutes);
        $c2 = Carbon::instance(new \DateTime($this->ended_at));
        $c2->addMinutes($minutes);
        return $this->update([
            'started_at' => $c1,
            'ended_at' => $c2
        ]);
    }

    public function addDowntime(int $minutes) {
        $c = Carbon::instance(new \DateTime($this->down_time));
        $c->addMinutes($minutes);
        return $this->update([
            'down_time' => $c
        ]);
    }

    public function worker(){
        // return $this->
    }

    // public function addMinutes(int $minutes) {
    //     $c1 = Carbon::instance(new \DateTime($this->time_planned));
    //     $c1->addMinutes($minutes);
    //     return $this->update([
    //         'time_planned' => $c1
    //     ]);
    // }

}
