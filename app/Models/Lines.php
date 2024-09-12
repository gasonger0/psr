<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Lines extends Model
{
    protected $table = 'lines';
    protected $primaryKey = 'line_id';
    public $incrementing = true;
    // protected $dateFormat = 'U';
    public $fillable = ['started_at', 'ended_at'];

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function addDowntime(int $minutes)
    {
        $c1 = Carbon::instance(new \DateTime($this->down_time, new \DateTimeZone('Europe/Moscow')));
        $c1->addMinutes($minutes);
        var_dump($c1);
        return $this->update([
            'down_time' => $c1
        ]);
    }
}
