<?php

namespace App\Http\Controllers;

use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\ProductsDictionary;
use Carbon\Carbon;
use Date;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsPlanController extends Controller
{
    // static public function getAll() {
    //     return ProductsPlan::all()->toJson();
    // }
    static public function getList(Request $request)
    {
        $date = $request->cookie('date');
        $isDay = boolval($request->cookie('isDay'));
        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::where('date', $date)
                ->where('isDay', $isDay)
                ->join('products_dictionary', 'products_plan.product_id', '=', 'products_dictionary.product_id')
                ->select('products_plan.*', DB::raw('products_dictionary.title as title'))
                ->get()
                ->toJson();
            // return ProductsPlan::all()->toJson();
        } else {
            return ProductsPlan::where('product_id', '=', $id)
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->get()
                ->toJson();
        }
    }

    public function addPlan(Request $request)
    {
        $date = $request->cookie('date');
        $isDay = boolval($request->cookie('isDay'));
        // $timeZone = new \DateTimeZone('Europe/Moscow');
        if ($post = $request->post()) {
            if (!isset($post['plan_product_id']) || $post['plan_product_id'] == null) {
                $plan = new ProductsPlan();
                $slot = ProductsSlots::find($post['slot_id'])->toArray();
                $plan->product_id = $slot['product_id'];
                $plan->line_id = $slot['line_id'];
                $plan->slot_id = $slot['product_slot_id'];
                $plan->workers_count = $slot['people_count'];
                $plan->type_id = $post['type_id'];
            } else {
                $plan = ProductsPlan::find($post['plan_product_id']);
            }
            $oldAm = $plan->amount;
            $plan->started_at = $post['started_at'];
            $plan->ended_at = $post['ended_at'];
            $plan->amount = $post['amount'];
            $plan->date = $date;
            $plan->isDay = $isDay;
            $plan->hardware = isset($post['hardware']) ? $post['hardware'] : 0;
            $plan->position = isset($post['position']) ? $post['position'] : 0;

            if (isset($post['colon'])) {
                $plan->colon = is_array($post['colon']) ? implode(';', $post['colon']) : $post['colon'];
            }
            $plan->save();

            $this->checkPlans($date, $isDay, $plan->line_id, $plan->plan_product_id, $post['started_at'], $post['ended_at'], $post['position']);
            if (!isset($post['delay']) || !$post['delay']) {
                ProductsPlan::where('product_id', '=', $plan->product_id)
                    ->where('type_id', '=', 2)
                    ->where('date', $date)
                    ->where('isDay', $isDay)
                    ->where('amount', $oldAm)
                    ->each(function($p) use($post, $date, $isDay) {
                        $p->amount = $post['amount'];
                        $prod = ProductsDictionary::where('product_id', '=', $p->product_id)->first();
                        $duration = ceil($post['amount'] * eval("return " . $prod['parts2kg'] . ";") * eval("return " . $prod['amount2parts'] . ";") / ($p->perfomance ? $p->perfomance : 1) * 60);
                        $p->started_at = new \DateTime($post['started_at']);
                        $p->ended_at = (new \DateTime($post['started_at']))->add(new \DateInterval('PT' . $duration . 'M'))->add(new \DateInterval('PT15M'))->format('H:i:s');
                        $p->save();
                        ProductsPlanController::checkPlans($date, $isDay, $p->line_id, $p->plan_product_id, $p->started_at, $p->ended_at, $p->position);
                    });
            }

            if (isset($post['delay']) && isset($post['packs'])) {

                foreach ($post['packs'] as $pack) {
                    $plan = ProductsPlan::where('slot_id', '=', $pack)->first();
                    if (!$plan) {
                        $plan = new ProductsPlan();
                    }
                    $slot = ProductsSlots::find($pack)->toArray();
		            $product = ProductsDictionary::where('product_id', '=', $slot['product_id'])->first();
                    $plan->date = $date;
                    $plan->isDay = $isDay;
                    $plan->product_id = $slot['product_id'];
                    $plan->line_id = $slot['line_id'];
                    $plan->slot_id = $slot['product_slot_id'];
                    $plan->workers_count = $slot['people_count'];
                    $plan->hardware = $post['hardware'];
                    $plan->type_id = 2;
                    $start = new \DateTime($post['started_at']);
                    $start->add(new \DateInterval('PT' . $post['delay'] . 'M'));
                    $plan->started_at = strval($start->format('H:i:s'));
                    $duration = ceil($post['amount'] * eval("return " . $product['parts2kg'] . ";") * eval("return " . $product['amount2parts'] . ";") / ($slot['perfomance'] ? $slot['perfomance'] : 1) * 60);
                    $start->add(new \DateInterval('PT' . $duration . 'M'));
                    $start->add(new \DateInterval('PT15M'));        // 15 минут на упаковку
                    $endPrev = (new \DateTime($post['ended_at']))->add(new \DateInterval('PT15M'))->add(new \DateInterval('PT' . $duration . 'M'));
                    if ($start < $endPrev) {
                        $plan->ended_at = $endPrev->format('H:i:s');
                    }else {
                        $plan->ended_at = $start->format('H:i:s');
                    }
                    $plan->amount = $post['amount'];
                    $plan->save();

                    $position = ProductsPlan::where('date', '=', $date)
                        ->where('isDay', '=', $isDay)
                        ->where('line_id', '=', $plan->line_id)
                        ->where('type_id', '=', 2)
                        ->where('started_at', '<', $plan->started_at)
                        ->max('position');
                    if ($position === null) {
                        $position = 0;
                    }
                    $plan->position = $position + 1;
                    $plan->save();

                    var_dump('Pack check. Current params:' . $plan->started_at . ', ' . $plan->ended_at);
                    // var_dump($plan->toArray());
                    $this->checkPlans($date, $isDay, $plan->line_id, $plan->plan_product_id, $plan->started_at, $plan->ended_at, $position);
                    // Добавка про 15 минут и проверка на конфликты каждый раз
                    // Узнать у натальи, ставим ли мы упааковку после текущей или вместо (скорее всего первое)
                    //}
                }
            }
            $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->orderBy('started_at', 'ASC')
                ->get()
                ->toArray();
            $minStartedAt = $plans ? min(array_column($plans, 'started_at')) : null;
            $maxEndedAt = $plans ? max(array_column($plans, 'ended_at')) : null;
            
            LinesExtraController::update($date, $isDay, $plan->line_id, ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]);

            return true;
        }
    }

    public static function checkPlans($date, $isDay, $line_id, $prod_id, $start, $end, $position) {
        // Проверка, не ставим ли мы план первым
        $check = ProductsPlan::where('line_id', '=', $line_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->where('plan_product_id', '!=', $prod_id)
            ->count();
        if ($check == 0) {
            return;
        }   
        // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
        $upPlan = ProductsPlan::where('position', '<=', $position)
            ->where('ended_at', '>=', $start)
            ->where('started_at', '<=', $start) 
            ->where('line_id', '=', $line_id)
            ->where('plan_product_id', '!=', $prod_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->orderBy('position', 'ASC')
            ->first();
        // Проверка - залезаем ли мы концом новой ГП на начало следующей
        $downPlan = ProductsPlan::where('position', '>=', $position)
            ->where('started_at', '<=', $end)
            ->where('ended_at', '>=', $end)
            ->where('line_id', '=', $line_id)
            ->where('plan_product_id', '!=', $prod_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->orderBy('position', 'ASC')
            ->first();


        if ($upPlan || $downPlan) {
            $topShift = $upPlan ? Carbon::parse($start)->diffInMinutes(Carbon::parse($upPlan->ended_at)) : 0;
            $downShift = $downPlan ? Carbon::parse($downPlan->started_at)->diffInMinutes(Carbon::parse($end)) : 0;
            $shift = $topShift + $downShift;
            if ($shift != 0) {
                $plan = ProductsPlan::where('plan_product_id', $prod_id)
                    ->where('date', $date)
                    ->where('isDay', $isDay)
                    ->where('line_id', $line_id)
                    ->where('position', '>', '0')
                    ->first();
                if ($plan){
                    $plan->update([
                        'started_at' => Carbon::parse($start)->addMinutes($topShift)->format('H:i:s'),
                        'ended_at' => Carbon::parse($end)->addMinutes($topShift)->format('H:i:s')
                    ]);
                }
                ProductsPlan::where('position', '>=', $position)
                    ->where('started_at', '>=', $start)
                    ->where('line_id', '=', $line_id)
                    ->where('plan_product_id', '!=', $prod_id)
                    ->where('date', $date)
                    ->where('isDay', $isDay)
                    ->each(function($p) use($shift) {
                        $p->started_at = Carbon::parse($p->started_at)->addMinutes($shift)->format('H:i:s');
                        $p->ended_at = Carbon::parse($p->ended_at)->addMinutes($shift)->format('H:i:s');
                        $p->position += 1;
                        $p->save();
                    });
            }
        }
    }
    public function delPlan(Request $request)
    {
        $plan = ProductsPlan::find($request->post('product_plan_id'));
        if ($plan) {
            ProductsPlan::where('product_id', '=', $plan->product_id)
                ->where('date', $plan->date)
                ->where('isDay', $plan->isDay)
                ->get()
                ->each(function ($item){
                    ProductsPlan::where('line_id', $item['line_id'])
                        ->where('position', '>', $item['position'])
                        ->where('date', $item['date'])
                        ->where('isDay', $item['isDay'])
                        ->each(function($el) {
                            $el->position = $el->position - 1;
                            $el->save();
                        });
                    $item->delete();
            });
            //delete();
        }
        $plan->delete();

        // Автоматом подтягивать время продукции в линиях, чтобы само вставало друг за другом, без промежутков ???
        return true;
    }

    public function changePlan(Request $request)
    {
        // Ид записей, которые надо менять местами и их порядок
        $data = $request->post();
        $date = $request->cookie('date');
        $isDay = boolval($request->cookie('isDay'));
        if (!$data) {
            return -1;
        } else {
            foreach ($data as $item) {
                $id = $item['plan_product_id'];
                unset($item['plan_product_id']);
                $prod = ProductsPlan::where('plan_product_id', '=', $id)->get()->first();
                $old_start = $prod->started_at;
                $prod->update($item);
                self::checkPlans($date, $isDay, $prod->line_id, $prod->plan_product_id, $prod->started_at, $prod->ended_at,$data['position']);
                $packs = ProductsPlan::where('product_id', '=', $prod->product_id)->where('type_id', '=', 2)->get();
                if ($packs) {
                    foreach ($packs as $pack) {
                        $start_diff = Carbon::parse($pack->started_at)->diffInMinutes(Carbon::parse($old_start));
                        $duration = abs(Carbon::parse($pack->ended_at)->diffInMinutes(Carbon::parse($pack->started_at)));
                        $pack->started_at = Carbon::parse($pack->started_at)->addMinutes($start_diff)->format('H:i:s');
                        $pack->ended_at = Carbon::parse($pack->started_at)->addMinutes($duration)->format('H:i:s');
                        $pack->save();
                        self::checkPlans($date, $isDay, $pack->line_id, $pack->plan_product_id, $pack->started_at, $pack->ended_at, $pack->position);
                    }
                }
            }
        }
    }

    public static function clear(Request $request)
    {

        $date = $request->post('date');
        $isDay = boolval($request->cookie('isDay'));
        if (!$date) {
            $date = $request->cookie('date');
        }
        ProductsPlan::where('date', $date)->where('isDay', $isDay)->delete();
        $def = LinesController::getDefaults();
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

    // public static function afterLineUpdate($line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    // {
    //     global $date;
    //     $slots = ProductsPlan::where('line_id', '=', $line_id)
    //         ->where('started_at', '=', $oldStart)
    //         ->where('date', $date)
    //         ->get();
    //     foreach ($slots as $slot) {
    //         $slot->started_at = $newStart;
    //         $slot->save();
    //     }

    //     $slots = ProductsPlan::where('line_id', '=', $line_id)
    //         ->where('ended_at', '=', $oldEnd)
    //         ->where('date', $date)
    //         ->get();
    //     foreach ($slots as $slot) {
    //         $slot->ended_at = $newEnd;
    //         $slot->save();
    //     }
    // }
}
