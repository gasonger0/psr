<?php

namespace App\Models;

use App\withSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Slots extends Model
{
    use withSession;
    protected $table = 'slots';
    protected $primaryKey = 'slot_id';
    public $incrementing = true;
    public $timestamps = false;
    // protected $dateFormat = 'U';
    protected $fillable = [
        'started_at', 
        'ended_at',
        'line_id',
        'worker_id',
        'date',
        'isDay' 
    ];
    use withSession;

    public function worker(){
        return $this->hasOne(Workers::class,'worker_id');
    }

    public function line() {
        return $this->hasOne(Lines::class,'line_id');
    }

}
