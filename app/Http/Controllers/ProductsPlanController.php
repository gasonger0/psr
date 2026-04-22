<?php

namespace App\Http\Controllers;

use App\Models\Lines;
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
    /* CRUD */

    /**
     * Получения плана. Если в запросе в есть product_Id, по которому нужно получить данные - 
     * возвращаются только планы по этому продукту. Иначе все планы по дню и смене из куки 
     */
    public function get(Request $request)
    {
        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::withSession($request)
                // ->joinProductTitle()
                ->get()
                ->toArray();
        } else {
            return ProductsPlan::where('product_id', '=', $id)
                ->withSession($request)
                ->get()
                ->toArray();
        }
    }

    /**
     * Добавление плана. В параметрах запроса может быть указан план по варке и планы по упаковке
     */
    public function create(Request $request)
    {
        Util::appendSessionToData($request);
        $plan = ProductsPlan::create($request->only((new ProductsPlan)->getFillable()));

        $line_id = $plan->slot->line_id;
        $order = array_replace(
            $this->checkPlans($request, $line_id),
            [$line_id => self::getByLine($line_id, $request)]
        );

        // Если задана упаковка... 
        if ($pack = $request->post('packs')) {
            $order = array_replace(
                $order,
                $this->processPacks($request, $pack, $plan)
            );
        }

        LinesController::updateLinesTime($order);
        return Util::successMsg($plan->toArray() + [
            'packs' => ProductsPlan::withSession($request)->where('parent', $plan->plan_product_id)->get(),
            'plansOrder' => $order
        ], 201);
    }

    public function update(Request $request)
    {
        Util::appendSessionToData($request);
        $plan = ProductsPlan::find($request->post('plan_product_id'));

        $order = [];
        $fields = $request->only((new ProductsPlan)->getFillable());

        $amount = $fields['amount'];

        if ($plan->slot->type_id == 1 || $amount != $plan->amount) {
            $pack = $request->post('packs');
            if ($plan->slot->type_id == 1) {
                $plan->update($fields);
            } else {
                $pack =
                    array_map(
                        fn($p) => $p['slot']['product_slot_id'],
                        ProductsPlan::withSession($request)
                            ->with('slot')
                            ->where('parent', $plan->parent)
                            ->orderBy('plan_product_id', 'ASC')
                            ->get()
                            ->toArray()
                    );
            }

            $line_id = $plan->slot->line_id;
            $order = $this->checkPlans($request, $line_id);

            if ($plan->slot->type_id != 1) {
                $parent_id = $plan->parent;
                $plan = ProductsPlan::find($parent_id);
                $request->merge([
                    'delay' => $plan->delay,
                ]);
                unset($parent_id);
            }

            // Удаляем упаковки по данной продукции
            ProductsPlan::where('parent', $plan->plan_product_id)
                ->each(function ($el) {
                    $el->delete();
                });

            if ($pack) {
                $order = array_replace(
                    $order,
                    $this->processPacks($request, $pack, $plan)
                );
            }

            // var_dump($pack, $pack);
        } else {
            switch ($plan->slot->type_id) {
                case 2:
                    // Упаковка
                    if ($plan->slot->line_id == 37) {
                        DB::beginTransaction();
                        $plan->update($fields);
                        $line_plans = self::checkPlans($request, 37, true);

                        /**
                         * 1) Получаем все, у кого parent и line_id != 37
                         *      (Варка не интересует, у неё parent = null, удобно)
                         * 2) Получаем 
                         */
                        foreach ($line_plans[37] as $crate) {
                            $plans = ProductsPlan::where('parent', $crate->parent)
                                ->get();

                            $latest = [
                                'start' => Carbon::parse($plans->max('started_at')),
                                'end' => Carbon::parse($plans->max('ended_at'))
                            ];

                            // Проверяем, что для каждого из них время 
                            // начинается не позже начала других этапов
                            if (Carbon::parse($crate->started_at)->isAfter($latest['start'])) {
                                DB::rollBack();
                                return Util::successMsg([
                                    37 => ProductsPlan::whereHas('slot', fn($q) => $q->where('line_id', 37))
                                        ->withSession($request)
                                        ->get()->toArray()
                                ]);
                            }

                            $duration = Util::calcDuration(
                                $crate->product,
                                $crate->amount,
                                $crate->slot
                            );

                            $ended_at = Carbon::parse($crate->start)->copy();
                            $ended_at->addHours($duration)->addMinutes(15);

                            if ($ended_at->isAfter($latest['end'])) {
                                $ended_at = $latest['end'];
                            }

                            $crate->update([
                                'started_at' => $crate->started_at,
                                'ended_at' => $ended_at
                            ]);
                        }
                        DB::commit();
                        $order = $this->checkPlans($request, 37);
                    } else {
                        $plan->update($fields);
                        $order = $this->checkPlans($request, $plan->slot->line_id);
                    }
                    break;
                case 3:
                case 5:
                    // Обновляем глазировку и последующую упаковку
                    $plan->update($fields);
                    $order = $this->checkPlans($request, $plan->slot->line_id);

                    $packs = ProductsPlan::where('parent', $plan->parent)
                        ->whereHas('slot', fn($q) => $q->where('type_id', 2))
                        ->with('slot')
                        ->get();

                    foreach ($packs as $pack) {
                        $duration = Util::calcDuration(
                            $pack->product,
                            $pack->amount,
                            $pack->slot
                        );

                        $start = Carbon::parse($plan->started_at);
                        $end = $start->copy()->addHours($duration)->addMinutes(15);
                        if ($end->isAfter($plan->ended_at)) {
                            $end = $plan->ended_at;
                        }
                        $pack->update([
                            'started_at' => $start,
                            'ended_at' => $end
                        ]);
                        $order = array_replace(
                            $order,
                            $this->checkPlans($request, $pack->slot->line_id)
                        );
                    }
                    break;
            }
        }

        LinesController::updateLinesTime($order);
        return Util::successMsg($plan->toArray() + [
            'plansOrder' => $order
        ], 200);
    }

    public function delete(Request $request)
    {
        // TODO проверка плана
        $id = $request->post('plan_product_id');
        $plan = ProductsPlan::find($id);
        if (!$plan) {
            return Util::errorMsg('Такого плана не существует', 404);
        }
        $line_ids = [
            $plan->slot->line_id => self::getByLine($plan->slot->line_id, $request)
        ];
        $plan->delete();
        $this->checkPlans($request, $plan->slot->line_id);
        ProductsPlan::where('parent', $id)->get()->each(function ($pack) use ($request, $line_ids) {
            $pack->delete();
            ProductsPlanController::checkPlans($request, $pack->slot->line_id);
            $line_ids[$pack->slot->line_id] = ProductsPlanController::getByLine($pack->slot->line_id, $request);
        });


        // TODO
        LinesController::updateLinesTime($line_ids);
        return Util::successMsg('План удалён', 200);
    }

    /* ACTIONS */

    /**
     * Проверка планов на коллизию
     * @param \Illuminate\Http\Request $request запрос с куками смены
     * @param \App\Models\ProductsPlan $plan план по изготовлению
     * @return bool
     */
    /**
     * Проверка планов на коллизию и автоматический сдвиг
     */
    public static function checkPlans(Request $request, int $lineId, bool $as_model = false): array
    {
        $allPlans = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->orderBy('plan_product_id', 'DESC')
            ->get();

        $order = [$lineId => ($as_model ? $allPlans : $allPlans->toArray())];
        // Если на линии всего один план, то на нём не может быть коллизий
        if (count($allPlans) == 1) {
            return $order;
        }


        // Считаем длительности для контроля и ищем налезания
        $prevPlan = null;

        for ($i = 0; $i < count($allPlans); $i++) {
            $pl = $allPlans[$i];

            $prevPlan = $i > 0 ? $allPlans[$i - 1] : null;

            if (!$prevPlan) {
                continue;
            }

            $topShift = null;

            // Считаем сдвиги
            if (
                !$topShift &&
                $pl->ended_at > $prevPlan->started_at &&
                $prevPlan->started_at > $pl->started_at
            ) {
                $topShift = Carbon::parse($prevPlan->started_at)->diffInMinutes($pl->ended_at);
            } else if (Carbon::parse($prevPlan->started_at)->diffInMinutes($pl->started_at) < 1) {
                $topShift = abs(Carbon::parse($prevPlan->started_at)->diffInMinutes($prevPlan->ended_at));
            } else if ($pl->started_at < $prevPlan->ended_at && $pl->started_at > $prevPlan->started_at) {
                $topShift = abs(Carbon::parse($prevPlan->ended_at)->diffInMinutes($pl->started_at));
            }


            // Применяем сдвиги, если они посчитаны
            if ($topShift != null) {
                $pl->update([
                    'started_at' => Carbon::parse($pl->started_at)->addMinutes($topShift),
                    'ended_at' => Carbon::parse($pl->ended_at)->addMinutes($topShift)
                ]);
            }

            $allPlans[$i] = $pl;

            ProductsPlan::where('parent', $pl->plan_product_id)
                ->whereHas('slot', function ($query) use ($lineId) {
                    $query->where('line_id', $lineId);
                })->withSession($request)
                ->orderBy('started_at', 'ASC')
                ->orderBy('plan_product_id', 'DESC')
                ->each(function ($p) use (&$order, $request, $as_model) {
                    $order = array_replace(
                        $order,
                        self::checkPlans($request, $p->slot->line_id, $as_model)
                    );
                });

        }

        return $order;
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
            return Util::errorMsg('Нет записей для смены порядка.', 404);
        }

        $order = [];

        foreach ($request->post() as $item) {
            // Находим план по ИД
            $plan = ProductsPlan::find($item['plan_product_id']);

            if ($plan->slot->type_id == 1) {
                // Обновляем модель
                $plan->update($item);
                // Список планов по упаковке, привязанных к текущему плану
                ProductsPlan::where('parent', $plan->plan_product_id)
                    ->withSession($request)
                    ->each(function ($pack) use ($plan, $request, &$order) {

                        // Получаем длительность в минутах
                        $duration = abs(Carbon::parse($pack->ended_at)
                            ->diffInMinutes(
                                Carbon::parse($pack->started_at)
                            ));

                        $newStart = Carbon::parse($plan->started_at)->addMinutes($plan->delay);

                        // Обновляем данные в модели
                        $pack->update([
                            'started_at' => $newStart,
                            'ended_at' => ($newStart->copy())->addMinutes($duration)
                        ]);

                        $lineId = $pack->slot->line_id;
                        // Проверка
                        $order = array_replace(
                            $order,
                            self::checkPlans($request, $lineId),
                            [$lineId => self::getByLine($lineId, $request)]
                        );
                    });
            } else {
                switch ($plan->slot->type_id) {
                    case 2:
                        // Упаковка
                        if ($plan->slot->line_id == 37) {
                            DB::beginTransaction();
                            $plan->update($item);
                            $line_plans = self::checkPlans($request, 37, true);

                            /**
                             * 1) Получаем все, у кого parent и line_id != 37
                             *      (Варка не интересует, у неё parent = null, удобно)
                             * 2) Получаем 
                             */
                            foreach ($line_plans[37] as $crate) {
                                $plans = ProductsPlan::where('parent', $crate->parent)
                                    ->get();

                                $latest = [
                                    'start' => Carbon::parse($plans->max('started_at')),
                                    'end' => Carbon::parse($plans->max('ended_at'))
                                ];

                                // Проверяем, что для каждого из них время 
                                // начинается не позже начала других этапов
                                if (Carbon::parse($crate->started_at)->isAfter($latest['start'])) {
                                    DB::rollBack();
                                    return Util::successMsg([
                                        37 => ProductsPlan::whereHas('slot', fn($q) => $q->where('line_id', 37))
                                            ->withSession($request)
                                            ->get()->toArray()
                                    ]);
                                }

                                $duration = Util::calcDuration(
                                    $crate->product,
                                    $crate->amount,
                                    $crate->slot
                                );

                                $ended_at = Carbon::parse($crate->start)->copy();
                                $ended_at->addHours($duration)->addMinutes(15);

                                if ($ended_at->isAfter($latest['end'])) {
                                    $ended_at = $latest['end'];
                                }

                                $crate->update([
                                    'started_at' => $crate->started_at,
                                    'ended_at' => $ended_at
                                ]);
                            }
                            DB::commit();
                            $order = $this->checkPlans($request, 37);
                        } else {
                            $plan->update($item);
                            $order = $this->checkPlans($request, $plan->slot->line_id);
                        }
                        break;
                    case 3:
                    case 5:
                        // Обновляем глазировку и последующую упаковку
                        $plan->update($item);
                        $order = $this->checkPlans($request, $plan->slot->line_id);

                        $packs = ProductsPlan::where('parent', $plan->parent)
                            ->whereHas('slot', fn($q) => $q->where('type_id', 2))
                            ->with('slot')
                            ->get();

                        foreach ($packs as $pack) {
                            $duration = Util::calcDuration(
                                $pack->product,
                                $pack->amount,
                                $pack->slot
                            );

                            $start = Carbon::parse($plan->started_at);
                            $end = $start->copy()->addHours($duration)->addMinutes(15);
                            if ($end->isAfter($plan->ended_at)) {
                                $end = $plan->ended_at;
                            }
                            $pack->update([
                                'started_at' => $start,
                                'ended_at' => $end
                            ]);
                            $order = array_replace(
                                $order,
                                $this->checkPlans($request, $pack->slot->line_id)
                            );
                        }
                        break;
                }
            }
        }

        LinesController::updateLinesTime($order);
        // Обновляённый порядок
        return Util::successMsg($order, 202);
    }

    /**
     * 1) Получаем все планы на данной линии, сортируем по возрастанию позиции
     * 2) Для каждого из них смотрим, есть ли упаковка и какой с ней интервал. Если будет несколько ГП на варку и на упаковку одного и того же продукта, надо как-то разруливать наверное?
     * 3) Считаем интервал с упаковкой, двигаем текущий план и делаем checkplans для упаковки
     * 4) Если наш план - это и есть упаковка, то упаковку не ищем 
     */
    /**
     * @deprecated не используется
     */
    public static function composePlans(ProductsPlan $plan, int $delay = 0)
    {
        $session = [
            'date' => $plan->date,
            'isDay' => $plan->isDay
        ];
        $line = LinesExtra::where('line_id', $plan->slot->line_id)->withSession($session)->first();
        $prevEnd = Carbon::parse($line->started_at)->addMinutes($line->prep_time);

        ProductsPlan::withSession($session)
            ->whereHas('slot', function ($query) use ($plan) {
                $query->where('line_id', $plan->slot->line_id);
            })
            ->orderBy('started_at', 'ASC')
            ->each(function ($item) use (&$prevEnd) {
                $duration = Carbon::parse($item->started_at)->diffInMinutes(Carbon::parse($item->ended_at));
                $item->started_at = $prevEnd;
                $item->save();
                $prevEnd = $prevEnd->addMinutes($duration);
                $item->ended_at = $prevEnd;
                $item->save();

                if ($item->parent == null) {
                    ProductsPlan::where('parent', $item->plan_product_id)->each(function ($p) use ($item) {
                        ProductsPlanController::composePlans($p, $item->delay);
                    });
                }
            });
    }

    public function clear(Request $request)
    {
        ProductsPlan::withSession($request)->each(function ($plan) {
            $plan->delete();
        });
        $lines = [];
        LinesExtra::withSession($request)->each(function ($line) use ($request, &$lines) {
            $default = Util::getDefaults($line->line_id);
            $default ? $default = Util::createDate($default, $request, $line->lines) : '';
            if ($default) {
                $line->update([
                    'started_at' => $default['started_at'],
                    'ended_at' => $default['ended_at']
                ]);
            }
            // var_dump($default)
            $default['line_id'] = $line->line_id;
            $lines[] = $default;
        });

        return Response($lines, 200);
    }

    private function processPacks(Request $request, array $pack_ids, ProductsPlan $plan): array
    {
        // Задержка от начала варки
        $delay = $request->post('delay');
        // Продукция
        $product = ProductsDictionary::find($plan->slot->product_id);
        // И объём
        $amount = $request->post('amount');
        $order = [];

        $packsGlazCheck = [];

        if (count($pack_ids) > 0) {
            foreach ($pack_ids as $pack_id) {
                /**
                 * Правила:
                 * 1) Обсыпка идёт параллельно варке
                 * 2) Глазировка - через интервал от варки
                 * 3) Опудривание - через интервал от варки
                 * 4) Упаковка - Параллельно глазировке/опудриванию.
                 *    Упаковка не может закончиться ранне любого другого этапа.
                 *    Если заканчивает раньше - pack.ended_at = glaz.ended_at || pudra.ended_at.
                 * 
                 * 5) НИ ОДИН ИЗ ЭТАПОВ НЕ МОЖЕТ КОНЧИТЬСЯ РАНЬШЕ, ЧЕМ boil.ended_at + delay + 15
                 * 6) 
                 */


                // Конец упаковки должен быть:
                // 1. Не раньше конца варки 
                // 2. Если раньше конца варки, то не раньше конца варки + delay
                // 3. Упаковка не позже обсыпки
                // 4. Флоу паки должны без задержки начинаться
                // 5. Обсыпка и упаковка через $delay минут, а глазировка и варка - юез задержки

                $slot = ProductsSlots::find($pack_id);

                $duration = Util::calcDuration(
                    $product,
                    $amount,
                    $slot
                );

                $start = Carbon::parse($plan->started_at);

                // Если не варка, не обсыпка и не упаковка ящиков - доюавляем задержку
                if ($slot->type_id != 1 && $slot->type_id != 4 && $slot->line_id != 37) {
                    $start->addMinutes($delay);
                }

                // Считаем время окончания с учётом времени на переход
                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);

                $boil_end = Carbon::parse($plan->ended_at)->addMinutes(15)->addMinutes($delay);

                if ($ended_at < $boil_end && $slot->line_id != 37) {
                    $ended_at = $boil_end;
                }

                // Обсыпка идёт ровно столько, сколько и варка
                if ($slot->type_id == 4) {
                    $start = Carbon::parse($plan->started_at);
                    $ended_at = Carbon::parse($plan->ended_at);
                }

                $packPlan = ProductsPlan::create(
                    [
                        'product_id' => $product->product_id,
                        'slot_id' => $pack_id,
                        'started_at' => $start,
                        'ended_at' => $ended_at,
                        'parent' => $plan->plan_product_id,
                        'amount' => $amount
                    ] + Util::getSessionAsArray($request)
                );

                // Если упаковка, запоминаем ИД для проверки глазировки
                if ($slot->type_id == 2 && $slot->line_id != 37) {
                    $packsGlazCheck[] = $packPlan;
                } else {
                    $line_id = $packPlan->slot->line_id;
                    $order = array_replace(
                        $order,
                        $this->checkPlans($request, $line_id),
                        [$line_id => self::getByLine($line_id, $request)]
                    );
                }
            }
        }


        // Проверка, что упаковываем не раньше глазировки/опудривания
        $glazPlans = ProductsPlan::where('parent', $plan->plan_product_id)
            ->whereHas('slot', function ($query) {
                $query->whereIn('type_id', [3, 5]);
            })->withSession($request)->latest('ended_at')->get();

        if ($glazPlans->isNotEmpty()) {
            $glaz_end = Carbon::parse($glazPlans->first()->ended_at);
            $glaz_start = Carbon::parse($glazPlans->first()->started_at);
        }
        foreach ($packsGlazCheck as $p) {
            $packEnd = Carbon::parse($p->ended_at);
            $packStart = Carbon::parse($p->started_at);

            // Если упаковываем раньше глазировки/обсыпки, двигаем
            if (isset($glaz_start) && $packStart < $glaz_start) {
                $diff = $packStart->diffInMinutes($glaz_start);
                $p->update([
                    'started_at' => $packStart->addMinutes($diff),
                    'ended_at' => $packEnd->addMinutes($diff),
                ]);
            }

            // Если поставили глазировку/обсыпку и она оканчивается позже упаковки
            if (isset($glaz_end) && $packEnd < $glaz_end) {
                // Сдвигаем упаковку так, чтобы она КОНЧАЛАСЬ НЕ РАНЬШЕ
                $p->update([
                    'ended_at' => $glaz_end
                ]);
            }

            $line_id = $p->slot->line_id;

            $order = array_replace(
                $order,
                $this->checkPlans($request, $line_id),
                [$line_id => self::getByLine($line_id, $request)],
            );

        }

        // Получаем все планы
        $plans = ProductsPlan::where('parent', $plan->plan_product_id)
            ->with('slot')
            ->withSession($request)
            ->orderBy('ended_at', 'DESC')
            ->get();

        if ($plans->isNotEmpty()) {
            // Находим самое позднее окончание планов
            // Можем закончить раннее упаковки, но не можем закончить позже 
            // варки, обсыпки, глазировки или опудривания
            $latestPlan = $plans
                ->filter(fn($q) => $q->slot->line_id != 37)
                ->first();
            $latest = Carbon::parse(
                $latestPlan->ended_at
            );

            // Находим упаковку ящиков по данной продукции
            $plans->filter(fn($q) => $q->slot->line_id == 37)
                ->each(function ($p) use ($latest, $request, &$order) {
                    // Если заканчиваем упаковывать ящики ПОЗЖЕ,
                    // чем заканчиваем любой этап (кроме варки),
                    // ставим окончание ящиков как самое позднее окончание 
    
                    if (Carbon::parse($latest)->diffInMinutes($p->ended_at) > 0) {
                        $p->update([
                            'ended_at' => $latest
                        ]);

                        $line_id = $p->slot->line_id;

                        $order = array_replace(
                            $order,
                            $this->checkPlans($request, $line_id),
                            [$line_id => self::getByLine($line_id, $request)],
                        );
                    }
                });
        }

        return $order;
    }

    private function getByLine(int $line_id, Request $request): array
    {
        return ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
            $query->where('line_id', $line_id);
        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
    }
}
