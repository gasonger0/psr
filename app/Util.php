<?php

namespace App;
use App\Models\Lines;
use App\Models\LinesDefault;
use App\Models\ProductsDictionary;
use App\Models\ProductsPlan;
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
        if ($line_id !== false) {
            $default = LinesDefault::where('line_id', $line_id)->first();
            if ($default) {
                $data = $default->toArray();
                unset($data['lines_default_id'], $data['line_id']);
                return $data;
            }

            // Fallback на старый конфиг, пока строки не перенесены в БД
            $defs = config('lines_defaults');
            $index = array_search($line_id, array_column($defs, 'line_id'));
            if ($index !== false) {
                return $defs[$index];
            }
            return false;
        }

        $db = LinesDefault::get()->toArray();
        if (count($db) > 0) {
            return array_map(function ($item) {
                unset($item['lines_default_id']);
                return $item;
            }, $db);
        }

        return config('lines_defaults');
    }

    /**
     * Сохраняет значение параметра линии по умолчанию.
     *
     * @param int $line_id ИД линии
     * @param string $field Название поля
     * @param mixed $value Новое значение
     */
    public static function setDefault(int $line_id, string $field, $value): bool
    {
        $allowed = ['title', 'perfomance', 'started_at', 'ended_at', 'workers_count', 'prep_time', 'after_time'];
        if (!in_array($field, $allowed, true)) {
            return false;
        }

        $attributes = ['line_id' => $line_id];
        if (!LinesDefault::where('line_id', $line_id)->exists()) {
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
    public static function successMsg(array|string|null $data = null, int $status = 200)
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
     * Рассчитывает длительность по слоту
     * @param \App\Models\ProductsDictionary $product ГП
     * @param int $amount Объём изготовления
     * @param \App\Models\ProductsSlots $slot Слот изготовления
     * @return float
     */
    public static function calcDuration(ProductsDictionary $product, int $amount, ProductsSlots $slot): float
    {
        if ($slot->line_id == 37) {     // сборка ящиков
            // если  телевизоры, то по штукам в ящике + ящикам, иначе по ящикам
            $title = $slot->line->title;
            $newAmount = eval ("return $amount * $product->amount2parts;");
            if (mb_strpos($title, "телевизор") !== false) {
                $newAmount += $amount;
            }
            return eval ("return $newAmount / $slot->perfomance;");
        }
        return
            eval ("return $product->parts2kg*$amount*$product->amount2parts;") /
            $slot->perfomance;
    }

    /**
     * Расчёт длительности для завёрточных машин
     * @param \App\Models\ProductsDictionary $product ГП
     * @param int $amount Объём изготовления
     * @param int $hardware ЗМ
     * @return float|int
     */
    public static function calcDurationForZM(ProductsDictionary $product, int $amount, int $hardware): float
    {
        $duration = eval ("return $product->parts2kg*$amount*$product->amount2parts;") / 143.5;
        if ($hardware == 3) {
            $duration *= 2;
        }
        return $duration;
    }

    public static function getCurrentTime(Request $request): Carbon
    {
        $session_date = Carbon::parse(Util::getSessionAsArray($request)['date']);
        return Carbon::now("Europe/Moscow")
            ->setDate(
                $session_date->year,
                $session_date->month,
                $session_date->day
            );

    }

    public static function createDate(array $data, Request $request, Lines $line)
    {
        $isDay = $request->attributes->get("isDay");
        $date = Carbon::createFromFormat('Y-m-d', $request->attributes->get('date'));
        switch ($line->type_id) {
            case 1:
                $stime = $isDay ? [7, 45, 0] : [18, 30, 0];
                $etime = $isDay ? [18, 30, 0] : [5, 30, 0];
                $data['started_at'] = $date->setTime(...$stime)->format('Y-m-d H:i:s');
                $data['ended_at'] = $date->setTime(...$etime)->format('Y-m-d H:i:s');
                break;
            case 2:
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

    // TODO в параметры линий
    public static function calcReturnMass(array $line, array $sum, string $type): string|bool
    {
        $title = mb_strtolower($line['title']);
        $m = "<f>=(" . implode("+", $sum) . ") * ";
        if (str_contains($title, "непрерывная линия")) {
            return 25;
        } else if (str_contains($title, "шоколадная линия")) {
            return match ($type) {
                'z' => $m . 0.015 . "</f>",
                's' => $m . 0.00405 . "</f>"
            };
        } else if (str_contains($title, "полуавт")) {
            return $m . 0.025 . "</f>";
        } else if (str_contains($title, "shot")) {
            return $m . 0.005 . "</f>";
        }
        return false;
    }

    public static function getLinesPersonalTime(Request $request): array
    {
        $array = [];
        ProductsPlan::withSession($request)
            ->with(['slot', 'line', 'product'])
            ->each(function (ProductsPlan $pl) use (&$array) {
                $line_id = $pl->line->line_id;
                if (!isset($array[$line_id])) {
                    $array[$line_id] = [
                        'amount' => [
                            'z' => 0,
                            's' => 0,
                            'k' => 0
                        ],
                        // 'amountByPeopleHours' => 0,
                        'totalPeople' => 0
                    ];
                }

                $amount =
                    $pl->amount *
                    $pl->product->amount2parts *
                    $pl->product->parts2kg;

                if (mb_strpos(mb_strtolower($pl->product->title), 'зефир') !== false) {
                    $array[$line_id]['amount']['z'] += $amount;
                } else if (mb_strpos(mb_strtolower($pl->product->title), 'суфле') !== false) {
                    $array[$line_id]['amount']['s'] += $amount;
                } else if (mb_strpos(mb_strtolower($pl->product->title), 'конфет') !== false) {
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

    public static function makeCounts(int $row_index, array $product, string $letter = 'B', int $amount = null)
    {
        $index = ord($letter) - 65 + 1;
        $i = fn(int $m = 0) => chr($index + 65 + $m);

        $cars = $i(3) . "$row_index*$product[cars]";
        return [
            $index => (float)$amount,
            "<f>=" . $i(0) . "$row_index*$product[amount2parts]</f>",
            "<f>=" . $i(1) . "$row_index*$product[parts2kg]</f>",
            isset($product['kg2boil']) ? "<f>=" . $i(2) . "$row_index*$product[kg2boil]</f>" : 0,
            isset($product['cars']) ? "<f>=ROUNDDOWN($cars, 0)</f>" : 0,
            '<b>т</b>',
            "<f>=ROUNDUP((($cars) - " . $i(4) . "$row_index)*$product[cars2plates], 0)</f>",
            '<b>под</b>'
        ];
    }

    public static function makeResult(string $letter, array $sum, array $catRows, bool $is_boil)
    {
        $title = match ($letter) {
            'z' => "зефира",
            's' => "суфле",
            'k' => "конфет"
        };

        $mapCat = fn($l) => 
                count($catRows[$letter]) > 0 ? 
                    '<f>=' . 
                        implode('+', array_map(fn($r) => $l . $r, $catRows[$letter])) . "</f>"
                : '';
            
        return [
            1 =>  "<b>Итого $title</b>",
            4 =>  (count($sum[$letter][0]) > 0) ? "<f>=" . implode("+", $sum[$letter][0]) . "</f>" : '',
            5 =>  (count($sum[$letter][1]) > 0 && $is_boil) ? "<f>=" . implode("+", $sum[$letter][1]) . "</f>" : '',
            // 15 => $mapCat('P'),
            16 => $mapCat('Q'), 
            17 => $mapCat('R'), 
            18 => $is_boil ? $mapCat('S') : '', 
            // 19 => $is_boil ? $mapCat('T') : ''
        ];
    }
}
