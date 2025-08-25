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
            // Сразу посчитаем, во сколько начнём упаковывать
            $start = Carbon::parse($plan->started_at)->addMinutes($delay);
            // И объём
            $amount = $request->post('amount');
            $packsCheck = [];
            
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

                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);

                if ($ended_at < ($d = Carbon::parse($plan->ended_at))) {
                    $ended_at = $d->addMinutes($delay);
                }

                $packPlan = ProductsPlan::create(
                    [
                        'product_id' => $product->product_id,
                        'slot_id' => $p,
                        'started_at' => $start,
                        'ended_at' => $ended_at,
                        'parent' => $plan->plan_product_id,
                        'amount' => $amount
                    ] + Util::getSessionAsArray($request)
                );

                $this->checkPlans($request, $packPlan);

                $line_id = $packPlan->slot->line_id;

                $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                    $query->where('line_id', $line_id);
                })->orderBy('started_at', 'ASC')->get()->toArray();

                if ($slot->type_id == 2) {
                    $packsCheck[] = $packPlan;
                    // Если упаковка, запоминаем ИД и потом будем по другим позициям чекать
                }
            }

            // Проверка, что упаковываем не раньше глазировки

            $glaz = ProductsPlan::where('parent', $plan->plan_product_id)->whereHas('slot', function($query) {
                $query->whereIn('type_id', [3, 4]);
            })->max('ended_at');
            // var_dump($glaz);
            $glaz_end = Carbon::parse($glaz)->unix();
            foreach ($packsCheck as $p) {
                if (Carbon::parse($p->ended_at)->unix() < $glaz_end) {
                    $p->update([
                        'ended_at' => $glaz
                    ]);

                    $this->checkPlans($request, $p);
                    $line_id = $p->slot->line_id;

                    $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                        $query->where('line_id', $line_id);
                    })->orderBy('started_at', 'ASC')->get()->toArray();
                }
            }
        }

        // TODO? меняем время работы линии по началу первого и концу последнего плана
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
        })->orderBy('started_at', 'ASC')->get()->toArray();

        if ($pack = $request->post('packs')) {
            // Задержка от начала варки
            $delay = $request->post('delay');
            // Продукция
            $product = ProductsDictionary::find($plan->slot->product_id);
            // Сразу посчитаем, во сколько начнём упаковывать
            $start = Carbon::parse($plan->started_at)->addMinutes($delay);
            // И объём
            $amount = $request->post('amount');
            foreach ($pack as $p) {
                // Конец упаковки должен быть:
                // 1. Не раньше конца варки
                // 2. Если раньше конца варки, то не раньше конца варки + delay
                $slot = ProductsSlots::find($p);

                $duration = Util::calcDuration(
                    $product,
                    $amount,
                    $slot
                );

                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);

                if ($ended_at < ($d = Carbon::parse($plan->ended_at))) {
                    $ended_at = $d->addMinutes($delay);
                }

                $packPlan = ProductsPlan::where('parent', $plan->plan_product_id)
                    ->where('slot_id', $p)
                    ->get()
                    ->first();

                $data = [
                    'product_id' => $product->product_id,
                    'slot_id' => $p,
                    'started_at' => $start,
                    'ended_at' => $ended_at,
                    'parent' => $plan->plan_product_id,
                    'amount' => $amount
                ] + Util::getSessionAsArray($request);
                if ($packPlan) {
                    $packPlan->update($data);
                } else {
                    $packPlan = ProductsPlan::create($data);
                }

                $this->checkPlans($request, $packPlan);

                $line_id = $packPlan->slot->line_id;

                $order[$line_id] = ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
                    $query->where('line_id', $line_id);
                })->orderBy('started_at', 'ASC')->get()->toArray();
            }
        }

        // TODO? меняем время работы линии по началу первого и концу последнего плана
        return Util::successMsg($plan->toArray() + [
            'packs' => ProductsPlan::withSession($request)->where('parent', $plan->plan_product_id)->get(),
            'plansOrder' => $order
        ], 201);
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
     * @param int $position позиция плана, по которой его надо проверить
     * @return bool
     */
    public static function checkPlans(Request $request, ProductsPlan $plan): bool
    {
        // Проверка, не ставим ли мы план первым
        $check = ProductsPlan::whereHas('slot', function ($query) use ($plan) {
            // var_dump($plan->slot);
            $query->where('line_id', $plan->slot->line_id);
        })
            ->withSession($request)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->count();

        if ($check == 0) {
            return true;
        }

        // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
        $topPlan = ProductsPlan::whereHas('slot', function ($query) use ($plan) {
            $query->where('line_id', $plan->slot->line_id);
        })
            ->where('ended_at', '>=', $plan->started_at)
            ->where('started_at', '<=', $plan->started_at)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->withSession($request)
            ->orderBy('ended_at', 'DESC')
            ->first();

        // Проверка - залезаем ли мы концом новой ГП на начало следующей
        $bottomPlan = ProductsPlan::whereHas('slot', function ($query) use ($plan) {
            $query->where('line_id', $plan->slot->line_id);
        })->where('started_at', '<=', $plan->ended_at)
            ->where('ended_at', '>=', $plan->ended_at)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->first();

        if ($topPlan || $bottomPlan) {
            // Считаем сдвиги сверху и снизу
            $topShift = $topPlan ?
                Carbon::parse($plan->started_at)
                    ->diffInMinutes(Carbon::parse($topPlan->ended_at)) : 0;
            $bottomShift = $bottomPlan ?
                Carbon::parse($bottomPlan->started_at)
                    ->diffInMinutes(Carbon::parse($plan->ended_at)) : 0;

            if ($topShift + $bottomShift != 0) {
                // Флажок, выше или ниже плана текущая ГП
                $passed = false;
                // Двигаем верхние записи на $topSHift, а нижние - на $topShift + $bottomShift
                ProductsPlan::whereHas('slot', function ($query) use ($plan) {
                    $query->where('line_id', $plan->slot->line_id);
                })
                    ->withSession($request)
                    ->orderBy('started_at', 'ASC')
                    ->each(function ($l) use ($topShift, $bottomShift, &$passed, $plan) {
                        $l->update([
                            'started_at' => Carbon::parse($l->started_at)->addMinutes(
                                $topShift + ($passed ? $bottomShift : 0)
                            ),
                            'ended_at' => Carbon::parse($l->ended_at)->addMinutes(
                                $topShift + ($passed ? $bottomShift : 0)
                            )
                        ]);
                        if ($l->plan_product_id == $plan->plan_product_id) {
                            $passed = true;
                        }
                    });
            }
        }
        self::composePlans($plan);
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
        $prevEnd = Carbon::parse($line->started_at);

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
    }
}
