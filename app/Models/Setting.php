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
     * Читает настройку из БД. Возвращает $default, если ключа нет.
     */
    public static function get(string $key, $default = null)
    {
        $value = self::where('key', $key)->value('value');

        return $value !== null ? $value : $default;
    }
}
