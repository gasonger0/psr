<?php

namespace App;

use App\Models\Lines;
use App\Models\LinesDefault;
use App\Models\ProductsDictionary;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\ReturnType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Util
{
    /**
     * Получает стандартыне значения для линии
     *
     * @param  mixed  $line_id  ИД линии
     */
    public static function getDefaults($line_id = false): array|bool
    {
        if ($line_id !== false) {
            $default = LinesDefault::where('line_id', $line_id)->first();
            if (! $default) {
                return false;
            }
            $data = $default->toArray();
            unset($data['lines_default_id'], $data['line_id']);

            return $data;
        }

        return LinesDefault::get()->map(function ($item) {
            unset($item['lines_default_id']);

            return $item;
        })->toArray();
    }

    /**
     * Сохраняет значение параметра линии по умолчанию.
     *
     * @param  int  $line_id  ИД линии
     * @param  string  $field  Название поля
     * @param  mixed  $value  Новое значение
     */
    public static function setDefault(int $line_id, string $field, $value): bool
    {
        $allowed = ['perfomance', 'workers_count', 'prep_time', 'after_time'];
        if (! in_array($field, $allowed, true)) {
            return false;
        }

        $attributes = ['line_id' => $line_id];
        if (! LinesDefault::where('line_id', $line_id)->exists()) {
            $seed = self::getDefaults($line_id);
            if (is_array($seed) && $seed !== false) {
                $attributes = array_merge($attributes, array_intersect_key($seed, array_flip($allowed)));
            }
        }
        $attributes[$field] = $value;

        LinesDefault::updateOrCreate(['line_id' => $line_id], $attributes);

        return true;
    }

    /**
     * Проверяет добавляемые данные на наличие дубликатов
     *
     * @param  array  $fields  поля по которым проверка
     * @param  array  $values  значения
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
     *
     * @param  array|string  $data  Данные
     * @param  int  $status  HTTP-код
     * @return \Illuminate\Http\Response
     */
    public static function successMsg(array|string|null $data = null, int $status = 200)
    {
        if (is_string($data)) {
            return Response([
                'message' => [
                    'type' => 'success',
                    'title' => $data,
                ],
            ], $status);
        }

        return Response($data, $status);
    }

    /**
     * Генерирует ответ с ошибкой
     *
     * @param  array|string  $data  Данные
     * @param  int  $status  HTTP-код
     * @return \Illuminate\Http\Response
     */
    public static function errorMsg(array|string $data, int $status = 400)
    {
        if (is_string($data)) {
            return Response([
                'error' => $data,
            ], $status);
        }

        return Response($data, $status);
    }

    /**
     * Добавляет данные сессии (isDay, date) в переданный запрос
     *
     * @param  \Illuminate\Http\Request  $request  Запрос
     * @return void
     */
    public static function appendSessionToData(Request &$request)
    {
        $request->merge([
            'date' => $request->attributes->get('date') ?? null,
            'isDay' => $request->attributes->get('isDay') ?? null,
        ]);
    }

    public static function getSessionAsArray(Request $request): array
    {
        return [
            'date' => $request->attributes->get('date'),
            'isDay' => $request->attributes->get('isDay'),
        ];
    }

    /**
     * Рассчитывает длительность по слоту
     *
     * @param  \App\Models\ProductsDictionary  $product  ГП
     * @param  int  $amount  Объём изготовления
     * @param  \App\Models\ProductsSlots  $slot  Слот изготовления
     */
    public static function calcDuration(ProductsDictionary $product, int $amount, ProductsSlots $slot): float
    {
        if ($slot->line->type_id == 3) {     // сборка ящиков
            // если телевизоры, то по штукам в ящике + ящикам, иначе по ящикам
            $newAmount = eval("return $amount * $product->amount2parts;");
            if ($product->televisor) {
                $newAmount += $amount;
            }

            return eval("return $newAmount / $slot->perfomance;");
        }

        return
            eval("return $product->parts2kg*$amount*$product->amount2parts;") /
            $slot->perfomance;
    }

    public static function getCurrentTime(Request $request): Carbon
    {
        $session_date = Carbon::parse(Util::getSessionAsArray($request)['date']);

        return Carbon::now('Europe/Moscow')
            ->setDate(
                $session_date->year,
                $session_date->month,
                $session_date->day
            );

    }

    public static function createDate(array $data, Request $request, Lines $line)
    {
        $isDay = $request->attributes->get('isDay');
        $date = Carbon::createFromFormat('Y-m-d', $request->attributes->get('date'));
        switch ($line->type_id) {
            case 1:
                $stime = $isDay ? [7, 45, 0] : [18, 30, 0];
                $etime = $isDay ? [18, 30, 0] : [5, 30, 0];
                $data['started_at'] = $date->setTime(...$stime)->format('Y-m-d H:i:s');
                $data['ended_at'] = $date->setTime(...$etime)->format('Y-m-d H:i:s');
                break;
            case 2:
            case 3: // сборка ящиков: работает по графику упаковки
                $data['started_at'] = $date->setTime(8, 0, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');
                $data['ended_at'] = $date->setTime(20, 0, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');
                break;
        }

        // $stime = Carbon::createFromFormat('H:i', $data['started_at']);
        // $etime = Carbon::createFromFormat('H:i', $data['ended_at']);

        // $data['started_at'] = $date->setTime($stime->hour, $stime->minute, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');
        // $data['ended_at'] = $date->setTime($etime->hour, $etime->minute, 0)->addHours($isDay ? 0 : 12)->format('Y-m-d H:i:s');

        return $data;
    }

    /**
     * Правила возвратных масс задаются в справочнике return_types,
     * линия ссылается на режим через return_type.
     */
    public static function calcReturnMass(array $line, array $sum, string $type): string|bool
    {
        $mode = self::getReturnTypes()->get($line['return_type'] ?? 0);
        if (! $mode) {
            return false;
        }
        $m = '<f>=('.implode('+', $sum).') * ';
        if ($mode->formula_type === 'fixed') {
            return $mode->fixed_value !== null ? (float) $mode->fixed_value : false;
        }
        $coef = match ($type) {
            'z' => $mode->coef_z,
            's' => $mode->coef_s,
            'k' => $mode->coef_k,
            default => null,
        };

        return $coef !== null && $coef !== '' ? $m.$coef.'</f>' : false;
    }

    /**
     * Справочник режимов возвратных масс.
     */
    public static function getReturnTypes(): Collection
    {
        return ReturnType::all()->keyBy('return_type_id');
    }

    public static function getLinesPersonalTime(Request $request): array
    {
        $array = [];
        ProductsPlan::withSession($request)
            ->with(['slot', 'line', 'product'])
            ->each(function (ProductsPlan $pl) use (&$array) {
                $line_id = $pl->line->line_id;
                if (! isset($array[$line_id])) {
                    $array[$line_id] = [
                        'amount' => [
                            'z' => 0,
                            's' => 0,
                            'k' => 0,
                        ],
                        // 'amountByPeopleHours' => 0,
                        'totalPeople' => 0,
                    ];
                }

                $amount =
                    $pl->amount *
                    $pl->product->amount2parts *
                    $pl->product->parts2kg;

                if (mb_strpos(mb_strtolower($pl->product->title), 'зефир') !== false) {
                    $array[$line_id]['amount']['z'] += $amount;
                } elseif (mb_strpos(mb_strtolower($pl->product->title), 'суфле') !== false) {
                    $array[$line_id]['amount']['s'] += $amount;
                } elseif (mb_strpos(mb_strtolower($pl->product->title), 'конфет') !== false) {
                    $array[$line_id]['amount']['k'] += $amount;
                } else {
                    // Если не сработал ни один паттерн, считаем, что это зефир
                    $array[$line_id]['amount']['z'] += $amount;
                }

                // $array[$line_id]['amountByPeopleHours'] =
                //     $amount
                //     // / $pl->slot->perfomance
                //     * $pl->slot->people_count;
            });

        return $array;
    }

    public static function makeCounts(int $row_index, array $product, string $letter = 'B', ?int $amount = null)
    {
        $index = ord($letter) - 65 + 1;
        $i = fn (int $m = 0) => chr($index + 65 + $m);

        $cars = $i(3)."$row_index*$product[cars]";

        return [
            $index => (float) $amount,
            '<f>='.$i(0)."$row_index*$product[amount2parts]</f>",
            '<f>='.$i(1)."$row_index*$product[parts2kg]</f>",
            isset($product['kg2boil']) ? '<f>='.$i(2)."$row_index*$product[kg2boil]</f>" : 0,
            isset($product['cars']) ? "<f>=ROUNDDOWN($cars, 0)</f>" : 0,
            '<b>т</b>',
            "<f>=ROUNDUP((($cars) - ".$i(4)."$row_index)*$product[cars2plates], 0)</f>",
            '<b>под</b>',
        ];
    }

    public static function makeResult(string $letter, array $sum, array $catRows, bool $is_boil)
    {
        $title = match ($letter) {
            'z' => 'зефира',
            's' => 'суфле',
            'k' => 'конфет'
        };

        $mapCat = fn ($l) => count($catRows[$letter]) > 0 ?
                    '<f>='.
                        implode('+', array_map(fn ($r) => $l.$r, $catRows[$letter])).'</f>'
                : '';

        return [
            1 => "<b>Итого $title</b>",
            4 => (count($sum[$letter][0]) > 0) ? '<f>='.implode('+', $sum[$letter][0]).'</f>' : '',
            5 => (count($sum[$letter][1]) > 0 && $is_boil) ? '<f>='.implode('+', $sum[$letter][1]).'</f>' : '',
            // 15 => $mapCat('P'),
            16 => $mapCat('Q'),
            17 => $mapCat('R'),
            18 => $is_boil ? $mapCat('S') : '',
            // 19 => $is_boil ? $mapCat('T') : ''
        ];
    }
}
