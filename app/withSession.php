<?php
namespace App;

use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;


trait withSession
{
    /**
     * Добавляет глобальный scope с параметрами.
     *
     * @param Builder $query
     * @param array $params [ключ => значение]
     * @return Builder
     */
    public function scopeWithSession($query, Request|array $request)
    {
        $data = $request instanceof Request ? [
            'date' => $request->attributes->get('date') ?? null,
            'isDay' => $request->attributes->get('isDay') ?? null
        ] : $request;

        $query->where('date', $data['date'])->where('isDay', $data['isDay']);
        return $query;
    }
}
