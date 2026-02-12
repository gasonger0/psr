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
        $order = [];

        $line_id = $plan->slot->line_id;
        $this->checkPlans($request, $line_id);


        $order[$line_id] = self::getByLine($line_id, $request);

        // Если задана упаковка... 
        if (($pack = $request->post('packs'))) {
            $this->processPacks($request, $pack, $plan, $order);
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

        $plan->update($request->only((new ProductsPlan)->getFillable()));

        $order = [];
        $line_id = $plan->slot->line_id;
        $this->checkPlans($request, $line_id);
        // Обновляем данные модели, проверяем упаковку

        $order[$line_id] = self::getByLine($line_id, $request);

        // Удаляем упаковки по данной продукции
        ProductsPlan::where('parent', $plan->plan_product_id)
            ->each(function ($el) {
                $el->delete();
            });

        if (($pack = $request->post('packs'))) {
            $this->processPacks($request, $pack, $plan, $order);
        }

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
        $this->checkPlans($request, $plan->slot->line_id);
        ProductsPlan::where('parent', $id)->get()->each(function ($pack) use ($request) {
            $pack->delete();
            ProductsPlanController::checkPlans($request, $pack->slot->line_id);
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
    public static function checkPlans(Request $request, int $lineId)
    {
        $allPlans = ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->orderBy('plan_product_id', 'DESC')
            ->get();


        // Если на линии всего один план, то на нём не может быть коллизий
        if (count($allPlans) == 1) {
            return;
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
                !$topShift && (
                $pl->ended_at > $prevPlan->started_at && 
                $prevPlan->started_at > $pl->started_at 
                )
            ) { 
                $topShift = Carbon::parse($prevPlan->started_at)->diffInMinutes($pl->ended_at);
            } else if (Carbon::parse($prevPlan->started_at)->diffInMinutes($pl->started_at) < 1) {
                $topShift = abs(Carbon::parse($prevPlan->started_at)->diffInMinutes($prevPlan->ended_at));
            } else if($pl->started_at < $prevPlan->ended_at && $pl->started_at > $prevPlan->started_at) {
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
        }
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

        // ИД связанных линий, на которых нужно проверить коллизии
        $assocLines = [];

        $order = [];

        foreach ($request->post() as $item) {
            // Находим план по ИД
            $prod = ProductsPlan::find($item['plan_product_id']);
            // Обновляем модель
            $prod->update($item);

            // Список планов по упаковке, привязанных к текущему плану
            ProductsPlan::where('parent', $prod->plan_product_id)
                ->withSession($request)
                ->each(function ($pack) use ($prod, $request, &$assocLines) {

                    // Получаем длительность в минутах
                    $duration = abs(Carbon::parse($pack->ended_at)
                        ->diffInMinutes(
                            Carbon::parse($pack->started_at)
                        ));

                    $newStart = Carbon::parse($prod->started_at)->addMinutes($prod->delay);

                    // Обновляем данные в модели
                    $pack->update([
                        'started_at' => $newStart,
                        'ended_at' => ($newStart->copy())->addMinutes($duration)
                    ]);
                    // Добавляем для проверки позже 
                    $assocLines[] = $pack->slot->line_id;
                });
        }

        // Проверка
        array_map(function ($lineId) use ($request, $order) {
            self::checkPlans($request, $lineId);
            $order[$lineId] = self::getByLine($lineId, $request);
        }, array_unique($assocLines));

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

    private function processPacks(Request $request, array $pack_ids, ProductsPlan $plan, array &$order)
    {
        // Задержка от начала варки
        $delay = $request->post('delay');
        // Продукция
        $product = ProductsDictionary::find($plan->slot->product_id);
        // И объём
        $amount = $request->post('amount');

        $packsCheck = [];
        if (count($pack_ids) > 0) {
            foreach ($pack_ids as $pack_id) {
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
                // Если опудривани, обсыпка или упаковка - добавляем задержку
                if (
                    ($slot->type_id == 2 || $slot->type_id == 5 || $slot->type_id == 3)
                    // && !str_contains($slot->line->title, 'FLOY')
                ) {
                    $start->addMinutes($delay);
                }
                $ended_at = $start->copy();
                $ended_at->addHours($duration)->addMinutes(15);
                $d = Carbon::parse($plan->ended_at)->addMinutes(15)->addMinutes($delay);
                if ($ended_at < $d) {
                    $ended_at = $d;
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

                if ($slot->type_id == 2) {
                    $packsCheck[] = $packPlan;
                    // Если упаковка, запоминаем ИД и потом будем по другим позициям чекать
                } else {
                    $line_id = $packPlan->slot->line_id;

                    $this->checkPlans($request, $line_id);

                    $order[$line_id] = self::getByLine($line_id, $request);

                }
            }
        }

        // Проверка, что упаковываем не раньше глазировки
        $glazPlans = ProductsPlan::where('parent', $plan->plan_product_id)
            ->whereHas('slot', function ($query) {
                $query->whereIn('type_id', [3, 5]);
            })->withSession($request)->get();

        if ($glazPlans->isNotEmpty()) {
            // Находим самое позднее окончание глазировки
            $latestGlazEnd = $glazPlans->max(function ($plan) {
                return Carbon::parse($plan->ended_at);
            });
            $glaz_end = Carbon::parse($latestGlazEnd);
        }
        foreach ($packsCheck as $p) {
            $packEnd = Carbon::parse($p->ended_at);
            if (isset($glaz_end) && $packEnd < $glaz_end) {
                // Сдвигаем упаковку так, чтобы она КОНЧАЛАСЬ НЕ РАНЬШЕ глазировки
                $p->update([
                    'ended_at' => $glaz_end
                ]);
            }

            $line_id = $p->slot->line_id;

            $this->checkPlans($request, $line_id);
            $order[$line_id] = self::getByLine($line_id, $request);

        }

        // Сборка ящиков
        $plans = ProductsPlan::whereHas('slot', function ($query) use ($plan) {
            $query->where('product_id', $plan->plan_product_id);
        })->withSession($request)->get();
        if ($plans->isNotEmpty()) {
            // Находим самое позднее окончание планов
            $latest = $plans->max(function ($plan) {
                return Carbon::parse($plan->ended_at);
            });
            ProductsPlan::where('product_id', $plan->plan_product_id)->whereHas('slot', fn($p) => $p->line_id == 37)
                ->withSession($request)->get()->each(function ($p) use ($latest, $request, $order) {
                    if (Carbon::parse($p->ended_at)->diffInMinutes($latest) < 0) {
                        $p->update([
                            'ended_at' => Carbon::parse($latest)
                        ]);

                        $line_id = $p->slot->line_id;

                        $this->checkPlans($request, $line_id);

                        $order[$line_id] = self::getByLine($line_id, $request);
                    }
                });
        }
    }

    private function getByLine(int $line_id, Request $request): array
    {
        return ProductsPlan::whereHas('slot', function ($query) use ($line_id) {
            $query->where('line_id', $line_id);
        })->withSession($request)->orderBy('started_at', 'ASC')->get()->toArray();
    }
}
