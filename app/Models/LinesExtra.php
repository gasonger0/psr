<?php

namespace App\Models;
use App\Util;
use App\withSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LinesExtra extends Model
{
    use withSession;
    protected $table = 'lines_extra';
    protected $primaryKey = 'line_extra_id';
    public $incrementing = true;
    protected $dateFormat = 'U';
    public $fillable = [
        'line_id',
        'date',
        'isDay',
        'prep_time',
        'workers_count',
        'after_time',
        'master',
        'engineer',
        'has_detector',
        'detector_start',
        'detector_end',
        'extra_title',
        'down_from',
        'down_time',
        'started_at',
        'ended_at'
    ];

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function lines()
    {
        return $this->belongsTo(LinesExtra::class, 'line_id', 'line_id');
    }

    public function scopeGetOrInsert(Builder $query, Lines $line, Request $request): LinesExtra
    {
        $extra = $query->where('line_id', $line->line_id)->first();
        if ($extra) {
            return $extra;
        }
        $attributes = [];
        foreach ($query->getQuery()->wheres as $el) {
            if (isset($el['value'])) {
                $attributes[$el['column']] = $el['value'];
            }
        }
        // TODO почему-то не всегда сразу создаёт с параметрами сессии. Видимо, надо их заюивать до всех запросов
        return LinesExtra::create(
            ['line_id' => $line->line_id] +
            $attributes +
            Util::getDefaults($line->line_id) 
        );
    }
}
