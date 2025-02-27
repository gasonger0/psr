<?php

namespace App\Http\Controllers;

use App\Models\LinesExtra;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use App\Models\ProductsDictionary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsPlanController extends Controller
{
    static public function getAll() {
        return ProductsPlan::all()->toJson();
    }
    static public function getList(Request $request)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        if (!($id = $request->post('product_id'))) {
            return ProductsPlan::where('date', $date)
                ->join('products_dictionary', 'products_plan.product_id', '=', 'products_dictionary.product_id')
                ->select('products_plan.*', DB::raw('products_dictionary.title as title'))
                ->get()
                ->toJson();
            // return ProductsPlan::all()->toJson();
        } else {
            return ProductsPlan::where('product_id', '=', $id)
                ->where('date', $date)
                ->get()
                ->toJson();
        }
    }

    public function addPlan(Request $request)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
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
            $plan->started_at = $post['started_at'];
            $plan->ended_at = $post['ended_at'];
            $plan->amount = $post['amount'];
            $plan->date = $date;
            $plan->hardware = isset($post['hardware']) ? $post['hardware'] : 0;

            if (isset($post['colon'])) {
                $plan->colon = is_array($post['colon']) ? implode(';', $post['colon']) : $post['colon'];
            }
            $plan->save();

            $this->checkPlans($plan->line_id, $plan->plan_product_id, $post['started_at'], $post['ended_at']);
            
            if (isset($post['delay']) && isset($post['packs'])) {

                foreach ($post['packs'] as $pack) {
                    $plan = ProductsPlan::where('slot_id', '=', $pack)->first();
                    if (!$plan) {
                        $plan = new ProductsPlan();
                    }
                    $slot = ProductsSlots::find($pack)->toArray();
		            $product = ProductsDictionary::where('product_id', '=', $slot['product_id'])->first();
                    $plan->date = $date;
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
                    $plan->ended_at = $start->format('H:i:s');
                    $plan->amount = $post['amount'];
                    $plan->save();

                    var_dump('Pack check. Current params:' . $plan->started_at . ', ' . $plan->ended_at);
                    // var_dump($plan->toArray());
                    $this->checkPlans($plan->line_id, $plan->plan_product_id, $plan->started_at, $plan->ended_at);
                    // Добавка про 15 минут и проверка на конфликты каждый раз
                    // Узнать у натальи, ставим ли мы упааковку после текущей или вместо (скорее всего первое)
                    //}
                }
            }
            $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
                ->where('date', session('date') ?? (new \DateTime('now'))->format('Y-m-d'))
                ->orderBy('started_at', 'ASC')
                ->get()
                ->toArray();
            $minStartedAt = $plans ? min(array_column($plans, 'started_at')) : null;
            $maxEndedAt = $plans ? max(array_column($plans, 'ended_at')) : null;
            
            LinesExtraController::update($plan->line_id, ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]);

            return true;
        }
    }

    // public function checkPlans($line_id, $prod_id, $start, $end)
    // {
    //     $date = session('date') ?? (new \DateTime())->format('Y-m-d');
    //     // Получаем планы, которые внутри нового
    //     $plans = ProductsPlan::where('line_id', '=', $line_id)
    //     ->where('plan_product_id', '!=', $prod_id)
    //         ->where('ended_at', '>=', $start)
    //         ->where('date', $date)
    //         ->where('date', $date)
    //         ->where('started_at', '<=', $start)
    //         ->where('ended_at', '>=', $start)
    //         ->orderBy('started_at', 'ASC')
    //         ->get()
    //         ->toArray();
    //     if (count($plans) > 1) {
    //         $timeZone = new \DateTimeZone('Europe/Moscow'); 
    //         $plans = array_filter($plans, function ($item) use ($prod_id) {
    //             return $item['plan_product_id'] != $prod_id;
    //         });
    //         $planOne = $plans[0];
    //         // Если есть, то ставим новую ГП после той, внутрь которой попадаем и ставим новые ГП
    //         $diff = Carbon::parse($planOne['ended_at'], $timeZone)->diffInMinutes(Carbon::parse($start, $timeZone));
    //         $pl = ProductsPlan::where('plan_product_id', '=', $prod_id)->first();
    //         $pl->started_at = $planOne['ended_at'];
    //         $pl->ended_at = Carbon::parse($end, $timeZone)->addMinutes($diff)->format('H:i:s');
    //         $pl->save();
    //         $plans = ProductsPlan::where('line_id', '=', $line_id)
    //             ->where('started_at', '>=', $start)
    //             ->where('date', $date)
    //             ->where('plan_product_id', '!=', $prod_id)
    //             ->orderBy('started_at', 'ASC')
    //             ->get();
    //         $end = $pl->ended_at;
    //         foreach ($plans as $plan) {
    //             // Те, которые надо сдвинуть
    //             $plan->started_at = $end;
    //             $end = Carbon::parse($plan->ended_at, $timeZone)->addMinutes($diff)->format('H:i:s');
    //             $plan->ended_at = $end;
    //             $plan->save();
    //         }
    //     }
    // }

    // public function checkPackPlans($line_id, $prod_id, $start, $end)
    // {
    //     ///??????
    //     $date = session('date') ?? (new \DateTime())->format('Y-m-d');
    //     $timeZone = new \DateTimeZone('Europe/Moscow');
    //     // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
    //     $plans = ProductsPlan::where('line_id', '=', $line_id)
    //         ->where('plan_product_id', '!=', $prod_id)
    //         ->where('ended_at', '>=', $start)
    //         ->where('date', $date)
    //         ->orderBy('ended_at', 'DESC')
    //         ->first();
    //     if ($plans) {
    //         $plan = ProductsPlan::where('plan_product_id', '=', $prod_id)->where('date', '=', $date)->first();
    //         // Если такая есть, то считаем, сколько минут идёт перекрытие
    //         $diff = Carbon::parse($plan->started_at, $timeZone)->diff(Carbon::parse($lastPlan->ended_at, $timeZone));
    //         $plan->started_at = $lastPlan->ended_at;
    //         $plan->ended_at = Carbon::parse($plan->started_at, $timeZone)->addMinutes($diff)->format('H:i:s');
    //         $plan->save();
    //     }
    // }

    public function checkPlans($line_id, $prod_id, $start, $end) {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        // $timeZone = new \DateTimeZone('Europe/Moscow');
        // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
        $upPlan = ProductsPlan::where('line_id', '=', $line_id)
            ->where('plan_product_id', '!=', $prod_id)
            ->where('ended_at', '>=', $start)
            ->where('started_at', '<=', $start) 
            ->where('date', $date)
            ->orderBy('started_at', 'ASC')
            ->first();
        // Проверка - залезаем ли мы концом новой ГП на начало следующей
        $downPlan = ProductsPlan::where('line_id', '=', $line_id)
            ->where('plan_product_id', '!=', $prod_id)
            ->where('started_at', '>=', $end)
            ->where('ended_at', '<=', $end)
            ->where('date', $date)
            ->orderBy('started_at', 'ASC')
            ->first();


        if ($upPlan || $downPlan) {
            $topShift = $upPlan ? Carbon::parse($start)->diffInMinutes(Carbon::parse($upPlan->ended_at)) : 0;
            $downShift = $downPlan ? Carbon::parse($downPlan->started_at)->diffInMinutes(Carbon::parse($end)) : 0;
            $shift = $topShift + $downShift;
            if ($shift != 0) {
                $plan = ProductsPlan::where('plan_product_id', $prod_id)
                    ->where('date', $date)
                    ->where('line_id', $line_id)
                    ->first();
                $plan->update([
                    'started_at' => Carbon::parse($start)->addMinutes($topShift)->format('H:i:s'),
                    'ended_at' => Carbon::parse($end)->addMinutes($topShift)->format('H:i:s')
                ]);

                ProductsPlan::where('line_id', '=', $line_id)
                    ->where('plan_product_id', '!=', $prod_id)
                    ->where('started_at', '>=', $start)
                    ->where('date', $date)
                    ->each(function($p) use($shift) {
                        $p->started_at = Carbon::parse($p->started_at)->addMinutes($shift)->format('H:i:s');
                        $p->ended_at = Carbon::parse($p->ended_at)->addMinutes($shift)->format('H:i:s');
                        $p->save();
                    });
            }
        }
    }
    public function delPlan(Request $request)
    {
        $plan = ProductsPlan::find($request->post('product_plan_id'));
        if ($plan) {
            ProductsPlan::where('product_id', '=', $plan->product_id)->get()->each(function ($item) {
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
        if (!$data) {
            return -1;
        } else {
            foreach ($data as $item) {
                $id = $item['plan_product_id'];
                unset($item['plan_product_id']);
                $prod = ProductsPlan::where('plan_product_id', '=', $id)->get()->first();
                $old_start = $prod->started_at;
                $prod->update($item);
                self::checkPlans($prod->line_id, $prod->plan_product_id, $prod->started_at, $prod->ended_at);
                $packs = ProductsPlan::where('product_id', '=', $prod->product_id)->where('type_id', '=', 2)->get();
                if ($packs) {
                    foreach ($packs as $pack) {
                        $start_diff = Carbon::parse($pack->started_at)->diffInMinutes(Carbon::parse($old_start));
                        $duration = abs(Carbon::parse($pack->ended_at)->diffInMinutes(Carbon::parse($pack->started_at)));
                        $pack->started_at = Carbon::parse($pack->started_at)->addMinutes($start_diff)->format('H:i:s');
                        $pack->ended_at = Carbon::parse($pack->started_at)->addMinutes($duration)->format('H:i:s');
                        $pack->save();
                        self::checkPlans($pack->line_id, $pack->plan_product_id, $pack->started_at, $pack->ended_at);
                    }
                }
            }
        }
    }

    public static function clear(Request $request)
    {

        $date = $request->post('date');
        if (!$date) {
            $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        }
        ProductsPlan::where('date', $date)->delete();
        $def = LinesController::getDefaults();
        foreach ($def as $d) {
            LinesExtra::where('line_id', '=', $d['line_id'])->where('date', $date)->each(function ($item) { 
                $item->delete();
            });
        }
        LogsController::clear($date);
        SlotsController::clear($date);
        return;
    }

    // public static function afterLineUpdate($line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    // {
    //     $date = session('date') ?? (new \DateTime())->format('Y-m-d');
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
