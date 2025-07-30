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
    // protected $dateFormat = 'U';
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
        'ended_at',
        'cancel_reason'
    ];

    public $timestamps = false;


    public function lines()
    {
        return $this->belongsTo(Lines::class, 'line_id', 'line_id');
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

        $default = Util::getDefaults($line->line_id);
        $default ? $default = Util::createDate($default, $request) : '';

        return LinesExtra::create(
            ['line_id' => $line->line_id] +
            $attributes +
            (is_array($default) ? $default : [])
        );
    }
}
