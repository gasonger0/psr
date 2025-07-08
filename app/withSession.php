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
    public function scopeWithSession($query, Request $request)
    {
        // $data = $reuest->cookie('date')
        $query->where('date', $request->attributes->get('date') ?? null);

        $query->where('isDay', $request->attributes->get('isDay') ?? null);
        return $query;
    }
}
