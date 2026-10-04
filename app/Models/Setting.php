<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    /**
     * Читает настройку. Статический кэш — на время запроса
     * (после сохранения в UI настройка применится со следующего запроса).
     */
    public static function get(string $key, $default = null)
    {
        static $cache = null;
        if ($cache === null) {
            $cache = self::pluck('value', 'key')->toArray();
        }

        return $cache[$key] ?? $default;
    }
}
