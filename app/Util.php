<?php

namespace App;
use App\Models\ProductsDictionary;
use App\Models\ProductsSlots;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Util
{
    /**
     * Получает стандартыне значения для линии
     * @param mixed $line_id ИД линии
     */
    public static function getDefaults($line_id = false): array|bool
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
                return true;
            }
        } else {
            foreach ($fields as $field) {
                if ($model::where($field, $values[$field])->count() > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Генерирует успешный ответ
     * @param array|string $data Данные
     * @param int $status HTTP-код
     * @return \Illuminate\Http\Response
     */
    public static function successMsg(array|string|null $data, int $status = 200)
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

    /**
     * Генерирует ответ с ошибкой
     * @param array|string $data Данные
     * @param int $status HTTP-код
     * @return \Illuminate\Http\Response
     */
    public static function errorMsg(array|string $data, int $status = 400)
    {
        if (is_string($data)) {
            return Response([
                'error' => $data
            ], $status);
        }
        return Response($data, $status);
    }

    /**
     * Добавляет данные сессии (isDay, date) в переданный запрос
     * @param \Illuminate\Http\Request $request Запрос
     * @return void
     */
    public static function appendSessionToData(Request &$request)
    {
        $request->merge([
            'date' => $request->attributes->get('date') ?? null,
            'isDay' => $request->attributes->get('isDay') ?? null
        ]);
    }

    public static function getSessionAsArray(Request $request): array
    {
        return [
            'date' => $request->attributes->get('date'),
            'isDay' => $request->attributes->get('isDay')
        ];
    }

    /**
     * Рассчитывает длительность 
     * @param \App\Models\ProductsDictionary $product ГП
     * @param int $amount Объём изготовления
     * @param \App\Models\ProductsSlots $slot Слот изготовления
     * @return float
     */
    public static function calcDuration(ProductsDictionary $product, int $amount, ProductsSlots $slot): float
    {
        return 
            eval ("return $product->parts2kg*$amount*$product->amount2parts;") /
            $slot->perfomance;
    }

    public static function createDate(array $data, Request $request)
    {
        $isDay = $request->attributes->get("isDay");
        $date = Carbon::createFromFormat('Y-m-d', $request->attributes->get('date'));
        $stime = Carbon::createFromFormat('H:i', $data['started_at']);
        $etime = Carbon::createFromFormat('H:i', $data['ended_at']);

        $data['started_at'] = $date->setTime($stime->hour, $stime->minute, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');
        $data['ended_at'] = $date->setTime($etime->hour, $etime->minute, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');

        return $data;
    }
}
