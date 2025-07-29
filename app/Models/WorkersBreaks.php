<?php

namespace App\Models;

use App\Util;
use App\withSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WorkersBreaks extends Model
{
    use withSession;
    protected $table = 'workers_breaks';
    protected $primaryKey = 'break_id';
    public $incrementing = true;
    public $fillable = [
        'date',
        'isDay',
        'worker_id',
        'started_at',
        'ended_at'
    ];
    public $timestamps = false;

    public function worker() {
        return $this->belongsTo(Workers::class, 'worker_id');
    }

    public static function scopeGetOrInsert(Builder $query, Workers $worker, Request $request) {
        $break = WorkersBreaks::where('worker_id', $worker->worker_id)->withSession($request)->first();
        if ($break) {
            return $break;
        } else {
            $session = Util::getSessionAsArray($request);
            $date = Carbon::parse($session['date']);
            return WorkersBreaks::create([
                'worker_id' => $worker->worker_id,
                'started_at' => $date->setTime($session['isDay'] ? 12 : 0,0,0)->format('Y-m-d H:i:s'),
                'ended_at' => $date->copy()->addHour()->format('Y-m-d H:i:s'),
            ] + $session);
        }
    }
}
