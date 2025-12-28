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
        $order = [];
        $this->checkPlans($request, $plan);

        $line_id = $plan->slot->line_id;

        $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
            $query->where('line_id', $line_id);
        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
        // Если задана упаковка... (slot_id)
        if ($pack = $request->post('packs')) {
            // Задержка от начала варки
            $delay = $request->post('delay');
            // Продукция
            $product = ProductsDictionary::find($plan->slot->product_id);
            // И объём
            $amount = $request->post('amount');
            $packsCheck = [];

            foreach ($pack as $p) {
                // Конец упаковки должен быть:
                // 1. Не раньше конца варки 
                // 2. Если раньше конца варки, то не раньше конца варки + delay
                // 3. Упаковка не позже обсыпки
                // 4. Флоу паки должны без задержки начинаться
                // 5. Обсыпка и упаковка через $delay минут, а глазировка и варка - юез задержки
                $slot = ProductsSlots::find($p);
                $duration = Util::calcDuration(
                    $product,
                    $amount,
                    $slot
                );

                $start = Carbon::parse($plan->started_at);
                // Если варка или глазировка или опудривание, добавляем задержfку
                if ($slot->type_id == 2 || $slot->type_id == 5 || $slot->type_id == 3) {
                    $start->addMinutes($delay);
                }
                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);

                if ($ended_at < ($d = Carbon::parse($plan->ended_at))) {
                    $ended_at = $d->addMinutes($delay);
                }

                $packPlan = ProductsPlan::create(
                    [
                        'product_id' => $product->product_id,
                        'slot_id' => $p,
                        'started_at' => str_contains($slot->line->title, 'FLOY') ? $start->addMinutes(-$delay) : $start,
                        'ended_at' => $ended_at,
                        'parent' => $plan->plan_product_id,
                        'amount' => $amount
                    ] + Util::getSessionAsArray($request)
                );

                if ($slot->type_id == 2) {
                    $packsCheck[] = $packPlan;
                    // Если упаковка, запоминаем ИД и потом будем по другим позициям чекать
                } else {
                    $this->checkPlans($request, $packPlan);

                    $line_id = $packPlan->slot->line_id;

                    $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                        $query->where('line_id', $line_id);
                    })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
                }
            }

            // Проверка, что упаковываем не раньше глазировки

            // Проверка, что упаковываем не раньше глазировки
            $glazPlans = ProductsPlan::where('parent', $plan->plan_product_id)
                ->whereHas('slot', function ($query) {
                    $query->where('type_id', 3);
                })->withSession($request)->get();

            if ($glazPlans->isNotEmpty()) {
                // Находим самое позднее окончание глазировки
                $latestGlazEnd = $glazPlans->max(function ($plan) {
                    return Carbon::parse($plan->ended_at);
                });
                // var_dump($glaz);
                $glaz_end = Carbon::parse($latestGlazEnd);
                foreach ($packsCheck as $p) {
                    $packStart = Carbon::parse($p->started_at);
                    if ($packStart < $glaz_end) {
                        // Сдвигаем упаковку так, чтобы она начиналась после глазировки
                        $newStart = $glaz_end->copy();
                        $duration = Carbon::parse($p->ended_at)->diffInMinutes($packStart);
                        $newEnd = $newStart->copy()->addMinutes(-$duration);

                        $p->update([
                            'started_at' => $newStart,
                            'ended_at' => $newEnd
                        ]);

                        $this->checkPlans($request, $p);
                        $line_id = $p->slot->line_id;
                        $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                            $query->where('line_id', $line_id);
                        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
                    }
                }
            }
        }

        // TODO? меняем время работы линии по началу первого и концу последнего плана
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

        $plan->update($request->only((new ProductsPlan)->getFillable()));

        $order = [];
        $this->checkPlans($request, $plan);
        // Обновляем данные модели, проверяем упаковку

        $line_id = $plan->slot->line_id;

        $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
            $query->where('line_id', $line_id);
        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();

        // Если задана упаковка... (slot_id)
        if ($pack = $request->post('packs')) {
            // Задержка от начала варки
            $delay = $request->post('delay');
            // Продукция
            $product = ProductsDictionary::find($plan->slot->product_id);
            // И объём
            $amount = $request->post('amount');
            $packsCheck = [];

            // Удаляем те, что не указаны
            ProductsPlan::where('parent', $plan->plan_product_id)
                ->whereNotIn('slot_id', $pack)
                ->each(function ($el) {
                    $el->delete();
                });
            foreach ($pack as $p) {
                // Конец упаковки должен быть:
                // 1. Не раньше конца варки 
                // 2. Если раньше конца варки, то не раньше конца варки + delay
                // 3. Упаковка не позже обсыпки
                $slot = ProductsSlots::find($p);
                $duration = Util::calcDuration(
                    $product,
                    $amount,
                    $slot
                );

                $start = Carbon::parse($plan->started_at);
                // Если варка или глазировка, добавляем задержку
                if ($slot->type_id == 2 || $slot->type_id == 5 || $slot->type_id == 3) {
                    $start->addMinutes($delay);
                }
                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);

                if ($ended_at < ($d = Carbon::parse($plan->ended_at))) {
                    $ended_at = $d->addMinutes($delay);
                }

                $packPlan = ProductsPlan::where('slot_id', $p)
                    ->withSession($request)
                    ->first();
                $attrs = [
                    'product_id' => $product->product_id,
                    'slot_id' => $p,
                    'started_at' => str_contains($slot->line->title, 'FLOY') ? $start->addMinutes(-$delay) : $start,
                    'ended_at' => $ended_at,
                    'parent' => $plan->plan_product_id,
                    'amount' => $amount
                ];
                // var_dump($packPlan);
                if ($packPlan) {
                    $packPlan->update($attrs);
                } else {
                    $packPlan = ProductsPlan::create(
                        $attrs + Util::getSessionAsArray($request)
                    );
                }

                $this->checkPlans($request, $packPlan);

                $line_id = $slot->line_id;

                $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                    $query->where('line_id', $line_id);
                })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();

                if ($slot->type_id == 2) {
                    $packsCheck[] = $packPlan;
                    // Если упаковка, запоминаем ИД и потом будем по другим позициям чекать
                }

            }

            // Проверка, что упаковываем не раньше глазировки
            $glazPlans = ProductsPlan::where('parent', $plan->plan_product_id)
                ->whereHas('slot', function ($query) {
                    $query->where('type_id', 3);
                })->withSession($request)->get();

            if ($glazPlans->isNotEmpty()) {
                // Находим самое позднее окончание глазировки
                $latestGlazEnd = $glazPlans->max(function ($plan) {
                    return Carbon::parse($plan->ended_at);
                });
                // var_dump($glaz);
                $glaz_end = Carbon::parse($latestGlazEnd);
                foreach ($packsCheck as $p) {
                    $packStart = Carbon::parse($p->started_at);
                    if ($packStart < $glaz_end) {
                        // Сдвигаем упаковку так, чтобы она начиналась после глазировки
                        $newStart = $glaz_end->copy();
                        $duration = Carbon::parse($p->ended_at)->diffInMinutes($packStart);
                        $newEnd = $newStart->copy()->addMinutes(-$duration);

                        $p->update([
                            'started_at' => $newStart,
                            'ended_at' => $newEnd
                        ]);

                        $this->checkPlans($request, $p);
                        $line_id = $p->slot->line_id;
                        $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                            $query->where('line_id', $line_id);
                        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
                    }
                }
            }
        }

        // TODO? меняем время работы линии по началу первого и концу последнего плана

        LinesController::updateLinesTime($order);
        return Util::successMsg($plan->toArray() + [
            'packs' => ProductsPlan::withSession($request)->where('parent', $plan->plan_product_id)->get(),
            'plansOrder' => $order
        ], 200);
    }

    public function delete(Request $request)
    {
        $id = $request->post('plan_product_id');
        $plan = ProductsPlan::find($id);
        if (!$plan) {
            return Util::errorMsg('Такого плана не существует', 404);
        }
        $plan->delete();
        $this->checkPlans($request, $plan);
        ProductsPlan::where('parent', $id)->get()->each(function ($pack) use ($request) {
            $pack->delete();
            ProductsPlanController::checkPlans($request, $pack);
        });


        // TODO обновить время работы линии и подтянуть время продукции на линиях

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
    public static function checkPlans(Request $request, ProductsPlan $plan): bool
    {
        $lineId = $plan->slot->line_id;

        // Получаем ВСЕ планы на линии в порядке начала
        $allPlans = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })
            ->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->get();

        if (count($allPlans) == 1) {
            return true;
        }

        // Определяем, где должен стоять наш план
        $planStart = Carbon::parse($plan->started_at);
        $planDuration = Carbon::parse($plan->ended_at)->diffInMinutes($planStart);

        // Находим подходящее место для плана
        $currentTime = null;
        $gapFound = false;

        // Проходим по всем планам и ищем свободное место
        foreach ($allPlans as $existingPlan) {
            if ($existingPlan->plan_product_id == $plan->plan_product_id) {
                continue; // Пропускаем сам план (если он уже есть)
            }

            $existingStart = Carbon::parse($existingPlan->started_at);
            $existingEnd = Carbon::parse($existingPlan->ended_at);

            // Если это первый план, ищем начало рабочего дня
            if ($currentTime === null) {
                // Получаем время начала работы линии
                $lineExtra = LinesExtra::where('line_id', $lineId)
                    ->withSession($request)
                    ->first();
                $currentTime = Carbon::parse($lineExtra->started_at ?? '00:00');
            }

            // Проверяем, поместится ли план ДО текущего существующего плана
            if (!$gapFound && $planStart <= $existingStart) {
                // План должен быть вставлен ДО этого существующего плана
                $requiredEnd = $currentTime->copy()->addMinutes($planDuration);

                // Если помещается до существующего плана
                if ($requiredEnd <= $existingStart) {
                    // Устанавливаем план на найденное место
                    $plan->update([
                        'started_at' => $currentTime,
                        'ended_at' => $requiredEnd,
                    ]);
                    $gapFound = true;
                    break;
                }
            }

            // Перемещаем текущее время после этого плана
            $currentTime = max($currentTime, $existingEnd);
        }

        // Если не нашли места до какого-либо плана, ставим в конец
        if (!$gapFound) {
            if ($currentTime === null) {
                // Первый план на линии
                $lineExtra = LinesExtra::where('line_id', $lineId)
                    ->withSession($request)
                    ->first();
                $currentTime = Carbon::parse($lineExtra->started_at ?? '00:00');
            }

            $newStartedAt = $currentTime;
            $newEndedAt = $currentTime->copy()->addMinutes($planDuration);

            $plan->update([
                'started_at' => $newStartedAt,
                'ended_at' => $newEndedAt,
            ]);

            // Теперь нужно сдвинуть все последующие планы
            $shiftDuration = $planDuration;
            self::shiftPlans($request, $lineId, $plan, 0); // Сдвиг не нужен, план уже на своем месте
        }

        // После размещения плана проверяем и устраняем пересечения
        return self::resolveAllOverlaps($request, $lineId);
    }

    private static function resolveAllOverlaps(Request $request, int $lineId): bool
    {
        // Получаем все планы на линии
        $allPlans = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })
            ->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->get();

        $hasOverlaps = true;
        $maxIterations = 10; // Защита от бесконечного цикла

        while ($hasOverlaps && $maxIterations-- > 0) {
            $hasOverlaps = false;

            for ($i = 0; $i < count($allPlans) - 1; $i++) {
                $current = $allPlans[$i];
                $next = $allPlans[$i + 1];

                $currentEnd = Carbon::parse($current->ended_at);
                $nextStart = Carbon::parse($next->started_at);

                // Если есть пересечение или планы впритык
                if ($currentEnd > $nextStart) {
                    $hasOverlaps = true;
                    $overlapMinutes = $currentEnd->diffInMinutes($nextStart, false);

                    if ($overlapMinutes > 0) {
                        // Сдвигаем все последующие планы
                        $shiftMinutes = $overlapMinutes + 1; // +1 минута зазора

                        for ($j = $i + 1; $j < count($allPlans); $j++) {
                            $planToShift = $allPlans[$j];
                            $planToShift->update([
                                'started_at' => Carbon::parse($planToShift->started_at)->addMinutes($shiftMinutes),
                                'ended_at' => Carbon::parse($planToShift->ended_at)->addMinutes($shiftMinutes),
                            ]);
                        }

                        // Обновляем коллекцию после сдвига
                        $allPlans = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
                            $query->where('line_id', $lineId);
                        })
                            ->withSession($request)
                            ->orderBy('started_at', 'ASC')
                            ->get();

                        break; // Выходим из цикла и начинаем заново
                    }
                }
            }
        }

        return true;
    }

    /**
     * Сдвиг всех планов, которые начинаются НЕ РАНЬЕ текущего плана,
     * на указанное количество минут.
     */
    private static function shiftPlans(Request $request, int $lineId, ProductsPlan $currentPlan, int $shiftMinutes): void
    {
        $plansToShift = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })
            ->where('plan_product_id', '!=', $currentPlan->plan_product_id)
            ->withSession($request)
            ->where('started_at', '>=', $currentPlan->started_at)
            ->orderBy('started_at', 'ASC')
            ->get();

        foreach ($plansToShift as $plan) {
            $plan->update([
                'started_at' => Carbon::parse($plan->started_at)->addMinutes($shiftMinutes),
                'ended_at' => Carbon::parse($plan->ended_at)->addMinutes($shiftMinutes),
            ]);
        }
    }

    /**
     * Проверка пересечения двух планов (старая версия остаётся без изменений)
     */
    private static function plansOverlap(ProductsPlan $plan1, ProductsPlan $plan2): bool
    {
        $start1 = Carbon::parse($plan1->started_at);
        $end1 = Carbon::parse($plan1->ended_at);
        $start2 = Carbon::parse($plan2->started_at);
        $end2 = Carbon::parse($plan2->ended_at);

        return $start1 <= $end2 && $end1 >= $start2;
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

        foreach ($request->post() as $item) {
            // Находим план по ИД
            $prod = ProductsPlan::find($item['plan_product_id']);
            // Обновляем модель
            $prod->update($item);

            // Список планов по упаковке, привязанных к текущему плану
            ProductsPlan::where('parent', $prod->plan_product_id)
                ->whereHas('slot', function ($query) use ($prod) {
                    $query->where('line_id', $prod->slot->line_id);
                })
                ->withSession($request)
                ->each(function ($pack) use ($prod, $request) {
                    // Расчитываем время разницы в упаковке 
    
                    // Получаем длительность в минутах
                    $duration = Carbon::parse($pack->ended_at)
                        ->diffInMinutes(
                            Carbon::parse($pack->started_at)
                        );
                    // Обновляем данные в модели
                    $pack->started_at = Carbon::parse($pack->started_at)->addMinutes($prod->delay);
                    $pack->ended_at = Carbon::parse($pack->started_at)->addMinutes($duration);
                    $pack->save();

                    // Проверка
                    $this->checkPlans($request, $pack);
                });
        }

        return Response([
            'message' => [
                'type' => 'success',
                'title' => 'Порядок продукции обновлён'
            ]
        ], 200);
    }


    /**
     * 1) Получаем все планы на данной линии, сортируем по возрастанию позиции
     * 2) Для каждого из них смотрим, есть ли упаковка и какой с ней интервал. Если будет несколько ГП на варку и на упаковку одного и того же продукта, надо как-то разруливать наверное?
     * 3) Считаем интервал с упаковкой, двигаем текущий план и делаем checkplans для упаковки
     * 4) Если наш план - это и есть упаковка, то упаковку не ищем 
     */
    public static function composePlans(ProductsPlan $plan, int $delay = 0)
    {
        // TODO придумать, как обрабатывать задержку у дочерних планов?
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
    public function clear(Request $request)
    {
        ProductsPlan::withSession($request)->each(function ($plan) {
            $plan->delete();
        });
        $lines = [];
        LinesExtra::withSession($request)->each(function($line) use ($request, &$lines) {
            $default = Util::getDefaults($line->line_id);
            $default ? $default = Util::createDate($default, $request, $line->lines) : '';
            if ($default) {
                $line->update([
                    'started_at' => $default['started_at'],
                    'ended_at' => $default['ended_at']
                ]);
            }
            $default['line_id'] = $line->line_id;
            $lines[] = $default;
        });
        
        return Response($lines, 200);
    }
}
