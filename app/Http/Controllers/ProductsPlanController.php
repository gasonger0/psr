<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\ProductsDictionary;
use App\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductsPlanController extends Controller
{
    /* CRUD */

    /**
     * Получения плана. Если в запросе в есть product_Id, по которому нужно получить данные - 
     * возвращаются только планы по этому продукту. Иначе все планы по дню и смене из куки 
     */
    public function get(Request $request)
    {
        $query = ProductsPlan::withSession($request);
        
        if ($id = $request->post('product_id')) {
            $query->where('product_id', $id);
        }
        
        return $query->get()->toArray();
    }

    /**
     * Добавление плана. В параметрах запроса может быть указан план по варке и планы по упаковке
     */
    public function create(Request $request)
    {
        Util::appendSessionToData($request);
        $plan = ProductsPlan::create($request->only((new ProductsPlan)->getFillable()));
        
        $order = [];
        $this->updateLineOrders($request, $plan->slot->line_id, $order);
        
        if ($packs = $request->post('packs')) {
            $this->processPacks($request, $packs, $plan, $order);
        }

        LinesController::updateLinesTime($order);
        return Util::successMsg($plan->toArray() + [
            'packs' => $this->getChildPlans($request, $plan->plan_product_id),
            'plansOrder' => $order
        ], 201);
    }

    public function update(Request $request)
    {
        Util::appendSessionToData($request);
        $plan = ProductsPlan::find($request->post('plan_product_id'));
        
        $plan->update($request->only((new ProductsPlan)->getFillable()));
        
        $order = [];
        $this->updateLineOrders($request, $plan->slot->line_id, $order);
        
        $this->deleteChildPlans($plan->plan_product_id);
        
        if ($packs = $request->post('packs')) {
            $this->processPacks($request, $packs, $plan, $order);
        }

        LinesController::updateLinesTime($order);
        return Util::successMsg($plan->toArray() + [
            'packs' => $this->getChildPlans($request, $plan->plan_product_id),
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
        
        $order = [];
        $this->updateLineOrders($request, $plan->slot->line_id, $order);
        
        $plan->delete();
        $this->deleteAndUpdateChildren($request, $id, $order);
        
        LinesController::updateLinesTime($order);
        return Util::successMsg('План удалён', 200);
    }

    /* ACTIONS */

    /**
     * Проверка планов на коллизию и автоматический сдвиг
     * @param Request $request запрос с куками смены
     * @param int $lineId идентификатор линии
     * @param array|null $order результат проверки (по ссылке)
     */
    public static function checkPlans(Request $request, int $lineId, ?array &$order = null): void
    {
        $allPlans = self::getPlansForLine($request, $lineId);

        if (count($allPlans) <= 1) {
            if (isset($order)) {
                $order[$lineId] = $allPlans;
            }
            return;
        }

        for ($i = 1; $i < count($allPlans); $i++) {
            $currentPlan = $allPlans[$i];
            $prevPlan = $allPlans[$i - 1];

            $shift = self::calculateShift($currentPlan, $prevPlan);

            if ($shift > 0) {
                $currentPlan->update([
                    'started_at' => Carbon::parse($currentPlan->started_at)->addMinutes($shift),
                    'ended_at' => Carbon::parse($currentPlan->ended_at)->addMinutes($shift)
                ]);
                $allPlans[$i] = $currentPlan;
            }

            if (isset($order)) {
                self::checkChildPlans($request, $currentPlan, $order);
            }
        }
        
        if (isset($order)) {
            $order[$lineId] = $allPlans;
        }
    }

    /**
     * Смена порядка планов
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function change(Request $request): Response
    {
        if (empty($request->post())) {
            return Util::errorMsg('Нет записей для смены порядка.', 404);
        }

        $assocLines = [];
        $order = [];

        foreach ($request->post() as $item) {
            $prod = ProductsPlan::find($item['plan_product_id']);
            $prod->update($item);
            $this->updateChildPlans($request, $prod, $assocLines);
        }

        // Проверяем помещения и получаем обновленные планы
        foreach (array_unique($assocLines) as $lineId) {
            $this->updateLineOrders($request, $lineId, $order);
        }
        
        // КРИТИЧНО: После смены порядка проверяем упаковки относительно глазировки
        // Выполняем для каждого измененного плана
        foreach ($request->post() as $item) {
            $prod = ProductsPlan::find($item['plan_product_id']);
            $packsCheck = ProductsPlan::where('parent', $prod->plan_product_id)
                ->whereHas('slot', fn($q) => $q->where('type_id', 2))
                ->withSession($request)->get()->toArray();
            
            if (!empty($packsCheck)) {
                $this->adjustPacksForGlazing($request, $prod, $packsCheck, $order);
            }
        }

        LinesController::updateLinesTime($order);
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
    public static function composePlans(ProductsPlan $plan, int $delay = 0): void
    {
        $session = ['date' => $plan->date, 'isDay' => $plan->isDay];
        $line = LinesExtra::where('line_id', $plan->slot->line_id)->withSession($session)->first();
        $prevEnd = Carbon::parse($line->started_at)->addMinutes($line->prep_time);

        ProductsPlan::withSession($session)
            ->whereHas('slot', fn($q) => $q->where('line_id', $plan->slot->line_id))
            ->orderBy('started_at', 'ASC')
            ->each(function ($item) use (&$prevEnd) {
                $duration = Carbon::parse($item->started_at)->diffInMinutes(Carbon::parse($item->ended_at));
                $item->update([
                    'started_at' => $prevEnd,
                    'ended_at' => $prevEnd->copy()->addMinutes($duration)
                ]);
                $prevEnd = $prevEnd->addMinutes($duration);

                if (!$item->parent) {
                    ProductsPlan::where('parent', $item->plan_product_id)
                        ->each(fn($p) => self::composePlans($p, $item->delay));
                }
            });
    }

    public function clear(Request $request): Response
    {
        ProductsPlan::withSession($request)->each(fn($plan) => $plan->delete());
        $lines = [];
        
        LinesExtra::withSession($request)->each(function ($line) use ($request, &$lines) {
            $default = Util::getDefaults($line->line_id);
            
            if ($default) {
                $default = Util::createDate($default, $request, $line->lines);
                if ($default) {
                    $line->update([
                        'started_at' => $default['started_at'],
                        'ended_at' => $default['ended_at']
                    ]);
                }
            }
            
            $default['line_id'] = $line->line_id;
            $lines[] = $default;
        });

        return Response($lines, 200);
    }
    
    /**
     * Получить все планы по линии
     */
    private static function getPlansForLine(Request $request, int $lineId)
    {
        return ProductsPlan::whereHas('slot', function ($query) use ($lineId) {
            $query->where('line_id', $lineId);
        })->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->orderBy('plan_product_id', 'DESC')
            ->get();
    }
    
    /**
     * Рассчитать сдвиг для коллизии
     */
    private static function calculateShift($currentPlan, $prevPlan): int
    {
        $currentStart = Carbon::parse($currentPlan->started_at);
        $currentEnd = Carbon::parse($currentPlan->ended_at);
        $prevStart = Carbon::parse($prevPlan->started_at);
        $prevEnd = Carbon::parse($prevPlan->ended_at);
        
        // Если текущий план перекрывает предыдущий справа, сдвигаем его
        if ($currentEnd > $prevStart && $prevStart > $currentStart) {
            return $prevStart->diffInMinutes($currentEnd);
        }
        
        // Если они начинаются практически одновременно
        if ($prevStart->diffInMinutes($currentStart) < 1) {
            return abs($prevStart->diffInMinutes($prevEnd));
        }
        
        // Если текущий начинается до конца предыдущего
        if ($currentStart < $prevEnd && $currentStart > $prevStart) {
            return abs($prevEnd->diffInMinutes($currentStart));
        }
        
        return 0;
    }
    
    /**
     * Проверить дочерние планы
     */
    private static function checkChildPlans(Request $request, ProductsPlan $plan, array &$order): void
    {
        ProductsPlan::where('parent', $plan->plan_product_id)
            ->whereHas('slot', function ($query) use ($plan) {
                $query->where('line_id', $plan->slot->line_id);
            })->withSession($request)
            ->orderBy('started_at', 'ASC')
            ->orderBy('plan_product_id', 'DESC')
            ->each(function ($childPlan) use ($request, &$order) {
                self::checkPlans($request, $childPlan->slot->line_id, $order);
            });
    }

    private function processPacks(Request $request, array $pack_ids, ProductsPlan $plan, array &$order): void
    {
        $delay = $request->post('delay');
        $product = ProductsDictionary::find($plan->slot->product_id);
        $amount = $request->post('amount');

        $packsCheck = [];
        
        foreach ($pack_ids as $pack_id) {
            $packPlan = $this->createPackPlan($request, $plan, $product, $amount, $pack_id, $delay);
            
            if ($packPlan->slot->type_id == 2) {
                $packsCheck[] = $packPlan;
            } else {
                $this->updateLineOrders($request, $packPlan->slot->line_id, $order);
            }
        }

        $this->adjustPacksForGlazing($request, $plan, $packsCheck, $order);
        $this->updateBoxPlans($request, $plan, $order);
    }
    
    /**
     * Создать план упаковки
     */
    private function createPackPlan(Request $request, ProductsPlan $plan, ProductsDictionary $product, 
                                    int $amount, int $pack_id, int $delay): ProductsPlan
    {
        $slot = ProductsSlots::find($pack_id);
        $duration = Util::calcDuration($product, $amount, $slot);

        $start = Carbon::parse($plan->started_at);
        if (($slot->type_id == 2 || $slot->type_id == 5 || $slot->type_id == 3) && $slot->line_id != 37) {
            $start->addMinutes($delay ?? 0);
        }

        $ended_at = $start->copy()->addHours($duration)->addMinutes(15);
        $minEnd = Carbon::parse($plan->ended_at)->addMinutes(15)->addMinutes($delay ?? 0);
        
        if ($ended_at < $minEnd) {
            $ended_at = $minEnd;
        }

        if ($slot->type_id == 4) {
            $start = Carbon::parse($plan->started_at);
            $ended_at = Carbon::parse($plan->ended_at);
        }

        return ProductsPlan::create([
            'product_id' => $product->product_id,
            'slot_id' => $pack_id,
            'started_at' => $start,
            'ended_at' => $ended_at,
            'parent' => $plan->plan_product_id,
            'amount' => $amount
        ] + Util::getSessionAsArray($request));
    }
    
    /**
     * Отрегулировать упаковки относительно глазировки
     * ВАЖНО: перемещает весь блок упаковки (начало и конец), чтобы она была ПОСЛЕ глазировки
     */
    private function adjustPacksForGlazing(Request $request, ProductsPlan $plan, array $packsCheck, array &$order): void
    {
        $glazPlans = ProductsPlan::where('parent', $plan->plan_product_id)
            ->whereHas('slot', fn($q) => $q->whereIn('type_id', [3, 5]))
            ->withSession($request)->get();

        if ($glazPlans->isEmpty()) {
            return;
        }

        // Правильное получение конца глазировки
        $latestGlazPlan = $glazPlans->max(fn($p) => Carbon::parse($p->ended_at));
        $glaz_end = Carbon::parse($latestGlazPlan->ended_at);

        foreach ($packsCheck as $p) {
            $packEnd = Carbon::parse($p->ended_at);
            
            // Если упаковка заканчивается раньше глазировки - сдвигаем ВЕСЬ блок вперед
            if ($packEnd < $glaz_end) {
                $packStart = Carbon::parse($p->started_at);
                $duration = $packEnd->diffInMinutes($packStart);
                
                $p->update([
                    'started_at' => $glaz_end->copy(),
                    'ended_at' => $glaz_end->copy()->addMinutes($duration)
                ]);
            }
            $this->updateLineOrders($request, $p->slot->line_id, $order);
        }
    }
    
    /**
     * Обновить планы для сборки ящиков
     */
    private function updateBoxPlans(Request $request, ProductsPlan $plan, array &$order): void
    {
        $plans = ProductsPlan::whereHas('slot', fn($q) => $q->where('product_id', $plan->plan_product_id))
            ->withSession($request)->get();
            
        if ($plans->isEmpty()) {
            return;
        }

        // Правильное получение максимального конца
        $latestPlan = $plans->max(fn($p) => Carbon::parse($p->ended_at));
        $latest = Carbon::parse($latestPlan->ended_at);

        ProductsPlan::where('product_id', $plan->plan_product_id)
            ->whereHas('slot', fn($q) => $q->where('line_id', 37))
            ->withSession($request)->get()
            ->each(function ($p) use ($latest, $request, &$order) {
                if (Carbon::parse($p->ended_at)->diffInMinutes($latest) < 0) {
                    $p->update(['ended_at' => $latest]);
                    $this->updateLineOrders($request, $p->slot->line_id, $order);
                }
            });
    }

    /**
     * Вспомогательные методы для оптимизации и повторного использования
     */
    
    private function updateLineOrders(Request $request, int $line_id, array &$order): void
    {
        $this->checkPlans($request, $line_id, $order);
    }
    
    private function getChildPlans(Request $request, int $parent_id)
    {
        return ProductsPlan::withSession($request)
            ->where('parent', $parent_id)
            ->get();
    }
    
    private function deleteChildPlans(int $parent_id): void
    {
        ProductsPlan::where('parent', $parent_id)->each(fn($plan) => $plan->delete());
    }
    
    private function deleteAndUpdateChildren(Request $request, int $parent_id, array &$order): void
    {
        ProductsPlan::where('parent', $parent_id)->get()->each(function ($pack) use ($request, &$order) {
            $pack->delete();
            $this->updateLineOrders($request, $pack->slot->line_id, $order);
        });
    }
    
    private function updateChildPlans(Request $request, ProductsPlan $prod, array &$assocLines): void
    {
        ProductsPlan::where('parent', $prod->plan_product_id)
            ->withSession($request)
            ->each(function ($pack) use ($prod, &$assocLines) {
                $duration = $this->calculateDuration($pack);
                $newStart = Carbon::parse($prod->started_at)->addMinutes($prod->delay ?? 0);
                
                // БЕЗ проверки глазировки здесь - это будет сделано в методе change()
                // Это необходимо, так как в processPacks есть специальная проверка adjustPacksForGlazing
                $pack->update([
                    'started_at' => $newStart,
                    'ended_at' => $newStart->copy()->addMinutes($duration)
                ]);
                
                $assocLines[] = $pack->slot->line_id;
            });
    }
    
    private function calculateDuration(ProductsPlan $plan): int
    {
        return abs(Carbon::parse($plan->ended_at)
            ->diffInMinutes(Carbon::parse($plan->started_at)));
    }
}
