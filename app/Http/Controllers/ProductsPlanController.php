<?php

namespace App\Http\Controllers;

use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\ProductsDictionary;
use App\Util;
use Carbon\Carbon;
use Date;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductsPlanController extends Controller
{
    /**
     * Функция получения плана. Если в запросе в есть product_Id, по которому нужно получить данные - 
     * возвращаются только планы по этому продукту. Иначе все планы по дню и смене из куки 
     */
    public static function get(Request $request)
    {

        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::withSession($request)
                ->joinProductTitle()
                ->get()
                ->toJson();
        } else {
            return ProductsPlan::where('product_id', '=', $id)
                ->withSession($request)
                ->get()
                ->toJson();
        }
    }

    /**
     * Функция добавления плана в параметрах запроса может быть указан план по варке и планы по упаковке
     */
    public function add(Request $request)
    {
        // Получаем текущие день и смену
        $date = $request->cookie('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);

        // Проверка на идиота
        if ($post = $request->post()) {
            if (!isset($post['plan_product_id']) || $post['plan_product_id'] == null) {
                // Если нет plan_product_id - значит новый план. Создаём его и заполняем данными из слота, переданного в $request
                $plan = new ProductsPlan();
                $slot = ProductsSlots::find($post['slot_id'])->toArray();
                $plan->fill([
                    'product_id' => $slot['product_id'],
                    'line_id' => $slot['line_id'],
                    'slot_id' => $slot['product_slot_id'],
                    'workers_count' => $slot['people_count'],
                    'type_id' => $post['type_id'],
                ]);
                $oldAm = $post['amount'];
            } else {
                // Ищем уже существующий план
                $plan = ProductsPlan::find($post['plan_product_id']);
                // Получаем старый объём изготовления (для существующего плана) 
                $oldAm = $plan->amount;
            }
            // Заполняем данными из запроса
            $plan->fill([
                'started_at' => $post['started_at'],
                'ended_at' => $post['ended_at'],
                'amount' => $post['amount'],
                'date' => $date,
                'isDay' => $isDay,
                'hardware' => $post['hardware'] ?? 0,
                'position' => $post['position'],
            ]);

            // Уазываем данные о количестве варочных колонок
            if (isset($post['colon'])) {
                $plan->colon = is_array($post['colon']) ? implode(';', $post['colon']) : $post['colon'];
            }
            $plan->save();

            // Проверяем план на конфликты с другими позициями
            $this->checkPlans(
                $date,
                $isDay,
                $plan
            );

            // Если НЕ указана упаковка - обрабатываем ту, что есть в базе
            if (!isset($post['delay']) || !$post['delay']) {
                // Ищем продукцию упаковки со старым объёмом изготовления
                ProductsPlan::where('product_id', '=', $plan->product_id)
                    ->where('type_id', '=', 2)
                    ->where('date', $date)
                    ->where('isDay', $isDay)
                    ->where('amount', $oldAm)
                    ->each(function ($p) use ($post, $date, $isDay) {
                        // Обновлчем объём изготовления
                        $p->amount = $post['amount'];
                        // Получаем справочныее данные о продукции
                        $prod = ProductsDictionary::where('product_id', '=', $p->product_id)->first();

                        // Вычисляем длительность изготовления по формулам из справочных данных
                        $duration = ceil(
                            $post['amount'] *
                            eval ("return " . $prod['parts2kg'] . "*" . $post['amount'] . ";") *
                            eval ("return " . $prod['amount2parts'] . ";") /
                            ($p->perfomance ? $p->perfomance : 1) * 60
                        );

                        // Считаем начало, как начало варки продукции
                        $p->started_at = new \DateTime($post['started_at']);
                        // Считаем конец, как начало варки продукции  + длительность упаковки + время на переход
                        $p->ended_at = (new \DateTime($post['started_at']))
                            ->add(new \DateInterval('PT' . $duration . 'M'))
                            ->add(new \DateInterval('PT15M'));
                        $p->save();

                        // Проверяем, не конфликтует ли с другими позициями на данной линии
                        ProductsPlanController::checkPlans($date, $isDay, $p);
                    });
            }

            // Если же указана упаковка и задержка, обрабатываем те, что переданы в запросе
            // @TODO: Возможно имеет смысл грохать всё, что есть в базе ???
            if (isset($post['delay']) && isset($post['packs'])) {
                // Обходим каждый ИД
                foreach ($post['packs'] as $pack) {
                    // Ищем план упаковки, иначе создаём
                    $plan = ProductsPlan::where('slot_id', '=', $pack)->first();
                    if (!$plan) {
                        $plan = new ProductsPlan();
                    }
                    // Ищем слот
                    $slot = ProductsSlots::find($pack);
                    // И продукт
                    $product = ProductsDictionary::where('product_id', '=', $slot->product_id)->first();
                    // Заполняем план упаковки данными из слота, запроса и кук
                    $plan->fill([
                        'date' => $date,
                        'isDay' => $isDay,
                        'product_id' => $slot->product_id,
                        'line_id' => $slot->line_id,
                        'slot_id' => $slot->product_slot_id,
                        'workers_count' => $slot->people_count,
                        'amount' => $post['amount'],
                        'hardware' => $post['hardware'] ?? 0,
                        'type_id' => 2,
                    ]);

                    // Считаем начало упаковки, как начало варки + задержка
                    $start = (new \DateTime($post['started_at']))
                        ->add(new \DateInterval('PT' . $post['delay'] . 'M'));
                    // Не помню, нахера так, но раз написано, значит, надо было
                    $plan->started_at = strval($start->format('H:i:s'));
                    // Рассчитываем, сколько будет упаковываться продукция по формулам
                    $duration = ceil(
                        $post['amount'] *
                        eval ("return " . $product['parts2kg'] . ($pack == 37 ? '*' . $product['amount2parts'] : '') . ";") /
                            // eval("return " . $product['amount2parts'] . ";") / 
                        ($slot->perfomance ? $slot->perfomance : 1) * 60
                    );
                    // считаем время от начала + время изготовления
                    $start->add(new \DateInterval('PT' . $duration . 'M'));
                    // +15 минут на упаковку
                    $start->add(new \DateInterval('PT15M'));


                    // Конец упаковки должен быть:
                    // 1. Не раньше конца варки
                    // 2. Если раньше конца варки, то не раньше конца варки + delay
                    $endPrev = new \DateTime($post['ended_at']);
                    if ($start < $endPrev) {
                        $plan->ended_at = $endPrev->add(new \DateInterval('PT' . $post['delay'] . 'M'))->format('H:i:s');
                    } else {
                        $plan->ended_at = $start->format('H:i:s');
                    }
                    /*--*/

                    // $plan->amount = $post['amount'];
                    $plan->save();

                    // Считаем время для начала и конца
                    $dates = [
                        "start" => new \DateTime($plan->started_at),
                        "end" => new \DateTime($plan->ended_at)
                    ];

                    // Рассчёт на случай, если начинаем до полуночи, а заканчиваем после (без этого куска программа считала бы, что мы закончили раньше чем начали (?))
                    if ($plan->started_at > $plan->ended_at) {
                        $dates['end']->add(new \DateInterval('P1D'));
                    }

                    // Ищем, что у нас на смене на этой линии (для расчёта позиции)
                    $positions = ProductsPlan::where('date', '=', $date)
                        ->where('isDay', '=', $isDay)
                        ->where('line_id', '=', $plan->line_id)
                        ->where('type_id', '=', 2)
                        // ->where('plan_product_id', '!=', $plan->plan_product_id)
                        ->get();

                    $position = false;

                    foreach ($positions as $pos) {
                        $d2 = [
                            "start" => new \DateTime($pos->started_at),
                            "end" => new \DateTime($pos->ended_at)
                        ];
                        // Тот же финт, что и выше
                        if ($d2['start'] > $d2['end']) {
                            $d2['end']->add(new \DateInterval('P1D'));
                        }

                        // Смотрим, как у нас ложатся даты текущей упаковки на другие позиции
                        if (!$position) {
                            if ($d2['start'] < $dates['end'] || ($dates['start'] < $d2['start'] && $d2['start'] < $dates['end'])) {
                                $position = $pos->position + 1;
                            }
                        } else {
                            // Сюда падаем, если позиция уже посчитана, двигаем всё, что ниже неё вниз на 1
                            $pos->position += 1;
                            $pos->save();
                        }
                    }

                    // Если фолс, значит она первая
                    if ($position === FALSE) {
                        $position = 0;
                        $positions->each(function ($pos) use ($position) {
                            $pos->position += 1;
                            $pos->save();
                        });
                    }

                    //$plan->position = $position + 1;
                    $plan->save();

                    var_dump('Pack check. Current params:' . $plan->started_at . ', ' . $plan->ended_at);
                    // var_dump($plan->toArray());
                    $this->checkPlans($date, $isDay, $plan, $position);

                    $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
                        ->where('date', $date)
                        ->where('isDay', $isDay)
                        ->orderBy('position', 'ASC')
                        ->get()
                        ->toArray();
                    $minStartedAt = $plans[0]['started_at'];
                    $maxEndedAt = end($plans)['ended_at'];
                    LinesExtraController::update($date, $isDay, $plan->line_id, ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]);

                    // Добавка про 15 минут и проверка на конфликты каждый раз
                    // Узнать у натальи, ставим ли мы упааковку после текущей или вместо (скорее всего первое)
                    //}
                }
            }
            $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->orderBy('position', 'ASC')
                ->get()
                ->toArray();
            $minStartedAt = $plans[0]['started_at'];
            $maxEndedAt = end($plans)['ended_at'];

            LinesExtraController::update($date, $isDay, $plan->line_id, ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]);

            return true;
        }
    }

    /**
     * Проверка планов на коллизию
     * @param \Illuminate\Http\Request $request запрос с куками смены
     * @param \App\Models\ProductsPlan $plan план по изготовлению
     * @param int $position позиция плана, по которой его надо проверить
     * @return bool
     */
    public static function checkPlans(Request $request, ProductsPlan $plan, int $position = null): bool
    {
        if (!$position) {
            $position = $plan->position;
        }

        // Проверка, не ставим ли мы план первым
        $check = ProductsPlan::where('line_id', '=', $plan->line_id)
            ->withSession($request)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            // TODO Мб надо добавить проверку на позицию?
            ->count();

        if ($check == 0) {
            return true;
        }

        // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
        $topPlan = ProductsPlan::where('ended_at', '>', $plan->started_at)
            ->where('started_at', '<', $plan->started_at)
            ->where('position', '<=', $position)
            ->where('line_id', $plan->line_id)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->withSession($request)
            ->orderBy('position', 'DESC')
            ->first();

        // Проверка - залезаем ли мы концом новой ГП на начало следующей
        $bottomPlan = ProductsPlan::where('started_at', '<', $plan->ended_at)
            ->where('ended_at', '>', $plan->ended_at)
            ->where('position', '>=', $position)
            ->where('line_id', '=', $plan->line_id)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->withSession($request)
            ->orderBy('position', 'ASC')
            ->first();

        // TODO накатываем миграцию на полноценный datetime и дропаем это говнище

        if ($plan->started_at > $plan->ended_at) {
            // Пограничная позиция, надо сделать доп. проверку
            // Т.к. позиция пограничная, то сдвиг сверху остаётся, а вот сдвиг снизу надо считать иначе
            $change = ProductsPlan::where('position', '>=', $position)
                // ->where('started_at', '>', '20:00:00')
                ->where('line_id', '=', $plan->line_id)
                ->where('plan_product_id', '!=', $plan->plan_product_id)
                ->withSession($request)
                ->orderBy('ended_at', 'ASC')
                ->first();
            if ($change && $change->position >= $position) {
                $bottomPlan = $change;
            }
        }

        if ($topPlan || $bottomPlan) {
            // Считаем сдвиги сверху и снизу
            $topShift = $topPlan ?
                Carbon::parse($plan->started_at)
                    ->diffInMinutes(Carbon::parse($topPlan->ended_at)) : 0;
            $bottomShift = $bottomPlan ?
                Carbon::parse($bottomPlan->started_at)
                    ->diffInMinutes(Carbon::parse($plan->ended_at)) : 0;

            if ($plan->started_at > $plan->ended_at) {
                $bottomShift = $bottomPlan ?
                    Carbon::parse($bottomPlan->started_at)
                        ->diffInMinutes(Carbon::parse($plan->ended_at)
                            ->add('P1D')) : 0;
            }

            if ($topShift + $bottomShift != 0) {
                // Тут двигаем только вслучае, если позиция новой ГП ненулевая, 
                // потому что нет смысла двигать нулевую - она и так никуда не залезает 
                $p = ProductsPlan::where('plan_product_id', $plan->plan_product_id)
                    ->withSession($request)
                    ->where('line_id', $plan->line_id)
                    ->where('position', '>', '0')
                    ->first();

                // Обновляем модель при наличии
                if ($p) {
                    $p->update([
                        'started_at' => Carbon::parse($plan->started_at)->addMinutes($topShift),
                        'ended_at' => Carbon::parse($plan->ended_at)->addMinutes($topShift)
                    ]);
                }

                ProductsPlan::where('position', '>=', $position)
                    // ->where('started_at', '>', $plan->started_at)
                    ->where('line_id', '=', $plan->line_id)
                    ->where('plan_product_id', '!=', $plan->plan_product_id)
                    ->withSession($request)
                    ->each(function ($p) use ($topShift, $bottomShift) {
                        $p->started_at = Carbon::parse($p->started_at)
                            ->addMinutes($topShift + $bottomShift);
                        $p->ended_at = Carbon::parse($p->ended_at)
                            ->addMinutes($topShift + $bottomShift);
                        $p->position += 1;
                        $p->save();
                    });
            }
        }
        self::composePlans($plan, $request);
        return true;
    }

    public function delPlan(Request $request)
    {
        $plan = ProductsPlan::find($request->post('product_plan_id'));
        if ($plan) {
            ProductsPlan::where('product_id', '=', $plan->product_id)
                ->where('plan_product_id', '!=', $plan->plan_product_id)
                ->where('date', $plan->date)
                ->where('isDay', $plan->isDay)
                ->get()
                ->each(function ($item) {
                    ProductsPlan::where('line_id', $item['line_id'])
                        ->where('position', '>', $item['position'])
                        ->where('date', $item['date'])
                        ->where('isDay', $item['isDay'])
                        ->each(function ($el) {
                            $el->position = $el->position - 1;
                            $el->save();
                        });
                    $item->delete();
                });
        }

        $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
            ->where('date', $request->cookie('date'))
            ->where('isDay', filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN))
            ->orderBy('position', 'ASC')
            ->get()
            ->toArray();
        if ($plans) {
            $minStartedAt = $plans[0]['started_at'];
            $maxEndedAt = end($plans)['ended_at'];
            LinesExtraController::update(
                $request->cookie('date'),
                filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN),
                $plan->line_id,
                ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]
            );
        }
        $plan->delete();
        // Автоматом подтягивать время продукции в линиях, чтобы само вставало друг за другом, без промежутков ???
        return true;
    }

    /**
     * Смена порядка планов
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function change(Request $request): Response
    {
        // Ид записей, которые надо менять местами и их порядок
        if (empty($request->post())) {
            return Response([
                'error' => 'Нет записей для смены порядка.'
            ], 400);
        }

        foreach ($request->post() as $item) {
            // Находим план по ИД
            $id = $item['plan_product_id'];
            // unset($item['plan_product_id']); TODO ХЗ надо ли

            $prod = ProductsPlan::find($id);
            $old_start = $prod->started_at;

            // Обновляем модель
            $prod->update($item);

            // Проверяем, нужно ли менять порядок планов по продукции
            self::checkPlans($request, $prod, $item['position']);

            // Список планов по упаковке, привязанных к текущему плану
            $packs = ProductsPlan::where('product_id', '=', $prod->product_id)
                ->where('type_id', '=', 2)
                ->withSession($request)
                ->each(function ($pack) use ($old_start, $request) {
                    // Расчитываем время разницы в упаковке 
                    // TODO имеет смысл пихнуть как поле в базе
                    $start_diff = Carbon::parse($pack->started_at)
                        ->diffInMinutes(Carbon::parse($old_start));

                    // Получаем длительность в минутах
                    $duration = Carbon::parse($pack->ended_at)
                        ->diffInMinutes(
                            Carbon::parse($pack->started_at)
                        );
                    // Обновляем данные в модели
                    $pack->started_at = Carbon::parse($pack->started_at)->addMinutes($start_diff);
                    $pack->ended_at = Carbon::parse($pack->started_at)->addMinutes($duration);
                    $pack->save();

                    // Снова проверка
                    self::checkPlans($request, $pack);
                });
        }

        return Response([
            'message' => [
                'type' => 'success',
                'title' => 'Порядок продукции обновлён'
            ]
        ], 200);
    }

    public static function clear(Request $request)
    {

        $date = $request->post('date');
        $isDay = filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN);
        if (!$date) {
            $date = $request->cookie('date');
        }
        ProductsPlan::where('date', $date)->where('isDay', $isDay)->delete();
        $def = Util::getDefaults();
        foreach ($def as $d) {
            LinesExtra::where('line_id', '=', $d['line_id'])
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->each(function ($item) {
                    $item->delete();
                });
        }
        LogsController::clear($date, $isDay);
        SlotsController::clear($date, $isDay);
        return;
    }


    // После того, как всё скорректировали, надо сдвигать их по времени вплотную к друг другу
    // А если это ещё и Варка, то надо искать интервал с упаковкой и двигать её тоже
    public static function composePlans(ProductsPlan $plan, Request $request)
    {
        /**
         * 1) Получаем все планы на данной линии, сортируем по возрастанию позиции
         * 2) Для каждого из них смотрим, есть ли упаковка и какой с ней интервал. Если будет несколько ГП на варку и на упаковку одного и того же продукта, надо как-то разруливать наверное?
         * 3) Считаем интервал с упаковкой, двигаем текущий план и делаем checkplans для упаковки
         * 4) Если наш план - это и есть упаковка, то упаковку не ищем 
         */

        $prevEnd = false;

        ProductsPlan::withSession($request)
            ->where('line_id', $plan->line_id)
            ->orderBy('position', 'ASC')
            ->each(function ($item) use (&$prevEnd, $request) {
                if ($prevEnd == false) {
                    $prevEnd = Carbon::parse($item->ended_at);
                    return;
                }
                if ($prevEnd instanceof Carbon) {
                    $duration = Carbon::parse($item->started_at)->diffInMinutes(Carbon::parse($item->ended_at));
                    $item->started_at = $prevEnd;
                    $item->save();
                    $prevEnd = $prevEnd->addMinutes($duration);
                    $item->ended_at = $prevEnd;
                    $item->save();
                    return;
                }
                // if ($item->type_id == 2) {
                //     return;
                // }
                // $start = Carbon::parse($item->started_at);
                // ProductsPlan::where('date', $date)
                //     ->where('isDay', $isDay)
                //     ->where('product_id', $item->product_id)
                //     ->where('type_id', 2)
                //     ->each(function($pack) use($date, $isDay, $start, $prevEnd) {
                //         if ($prevEnd instanceof Carbon) {
                //             $delay = $start->diffInMinutes(Carbon::parse($pack->started_at));
                //             $duration = Carbon::parse($pack->started_at)->diffInMinutes(Carbon::parse($pack->ended_at));
                //             var_dump('delay: ' . $delay . ', duration: ' . $duration , ', prevend: ' . $prevEnd->format('H:i:s'));
                //             $pack->started_at = $prevEnd->addMinutes($delay);
                //             $pack->ended_at = $prevEnd->addMinutes($delay)->addMinutes($duration);
                //             $pack->save();
                //             ProductsPlanController::checkPlans($date, $isDay, $pack);
                //         }
                //     });
    
            });
    }
}
