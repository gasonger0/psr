<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Util
{
    public static function getDefaults($line_id = false)
    {
        $defs = config('lines_defaults');
        if ($line_id !== false) {
            $index = array_search($line_id, array_column($defs, 'line_id'));
            if ($index !== false) {
                return $defs[$index];
            } else {
                return false;
            }
        } else {
            return $defs;
        }
    }

    /**
     * Проверяет добавляемые данные на наличие дубликатов
     * @param Model $model
     * @param array $fields поля по которым проверка
     * @param array $values значения 
     * @return boolean
     */
    public static function checkDublicate(Model $model, array $fields, array $values, bool $strong = false): bool
    {
        if ($strong) {
            if ($model::where($values)->count() > 0) {
                return false;
            }
        } else {
            foreach ($fields as $field) {
                if ($model::where($field, $values[$field])->count() > 0) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function successMsg(array|string $data, int $status = 200)
    {
        if (is_string($data)) {
            return Response([
                'message' => [
                    'type' => 'success',
                    'title' => $data,
                ]
            ], $status);
        }
        return Response($data, $status);
    }

    public static function errorMsg(array|string $data, int $status = 400)
    {
        if (is_string($data)) {
            return Response([
                'error' => $data
            ], $status);
        }
        return Response($data, $status);
    }

    public static function appendSessionToData(array &$data, Request $request)
    {
        $data['date'] = $request->attributes->get('date') ?? null;
        $data['isDay'] = $request->attributes->get('isDay') ?? null;
    }
}
