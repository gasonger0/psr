<?php

namespace App\Http\Controllers;

use App\Models\Lines;
use App\Models\ProductsPlan;
use App\Models\ProductsSlots;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;


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
        if ($post = $request->post()) {
            if (!isset($post['plan_product_id'])) {
                $plan = new ProductsPlan();
                $slot = ProductsSlots::find($post['slot_id'])->toArray();
                $plan->product_id = $slot['product_id'];
                $plan->line_id = $slot['line_id'];
                $plan->slot_id = $slot['product_slot_id'];
                $plan->workers_count = $slot['people_count'];
                $plan->started_at = $post['started_at'];
                $plan->type_id = $post['type_id'];
                $plan->ended_at = $post['ended_at'];
                $plan->amount = $post['amount'];
                $plan->hardware = isset($post['hardware']) ? $post['hardware'] : 0;
                $plan->date = $date;
                if (isset($post['colon'])) {
                    $plan->colon = is_array($post['colon']) ? implode(';', $post['colon']) : $post['colon'];
                }
                $plan->save();


                $this->checkPlans($slot['line_id'], $plan->plan_product_id, $post['started_at'], $post['ended_at']);

                if (isset($post['delay']) && isset($post['packs'])) {
                    foreach ($post['packs'] as $pack) {
                        $plan = new ProductsPlan();
                        $slot = ProductsSlots::find($pack)->toArray();
                        $plan->product_id = $slot['product_id'];
                        $plan->line_id = $slot['line_id'];
                        $plan->slot_id = $slot['product_slot_id'];
                        $plan->workers_count = $slot['people_count'];
                        $plan->date = $date;
                        // $plan->hardware = $post['hardware'];
                        $plan->type_id = 2;

                        $start = new \DateTime($post['started_at']);

                        $start->add(new \DateInterval('PT' . $post['delay'] . 'M'));

                        $plan->started_at = $start->format('H:i:s');
                        $duration = ceil($post['amount'] / ($slot['perfomance'] ? $slot['perfomance'] : 1) * 60);

                        $start->add(new \DateInterval('PT' . $duration . 'M'));

                        $plan->ended_at = $start->format('H:i:s');
                        $plan->amount = $post['amount'];
                        $plan->save();

                        $this->checkPackPlans($slot['line_id'], $plan->plan_product_id, $plan->started_at, $plan->ended_at);
                    }
                }
                return true;
            } else {
                $old = ProductsPlan::find($post['plan_product_id']);

                $old->started_at = $post['started_at'];
                $old->ended_at = $post['ended_at'];
                $old->amount = $post['amount'];
                $old->hardware = isset($post['hardware']) ? $post['hardware'] : $old->hardware;

                if (isset($post['colon'])) {
                    $old->colon = is_array($post['colon']) ? implode(';', $post['colon']) : $post['colon'];
                }
                $old->save();

                $this->checkPlans($old->line_id, $old->plan_product_id, $post['started_at'], $post['ended_at']);
            }
            if (isset($post['delay']) && isset($post['packs'])) {
                foreach ($post['packs'] as $pack) {
                    // $plans = ProductsPlan::where('slot_id', '=', $pack)->get();
                    // if (!$plans) {
                        // $plans = new ProductsPlan();
                    // }
                    // foreach ($plans as $plan) {
                    $plan = ProductsPlan::where('slot_id', '=', $pack)->first();
                    if (!$plan) {
                        $plan = new ProductsPlan();
                    }
                    $slot = ProductsSlots::find($pack)->toArray();
                    $plan->product_id = $slot['product_id'];
                    $plan->line_id = $slot['line_id'];
                    $plan->slot_id = $slot['product_slot_id'];
                    $plan->workers_count = $slot['people_count'];
                    $plan->hardware = $post['hardware'];
                    $plan->type_id = 2;
                    $start = new \DateTime($post['ended_at']);
                    $start->add(new \DateInterval('PT' . $post['delay'] . 'M'));
                    $plan->started_at = $start->format('H:i:s');
                    $duration = ceil($post['amount'] / ($slot['perfomance'] ? $slot['perfomance'] : 1) * 60);
                    $start->add(new \DateInterval('PT' . $duration . 'M'));
                    $start->add(new \DateInterval('PT15M'));        // 15 минут на упаковку
                    $plan->ended_at = $start->format('H:i:s');
                    $plan->amount = $post['amount'];
                    $plan->save();

                    $this->checkPackPlans($old->line_id, $old->plan_product_id, $post['started_at'], $post['ended_at']);
                    // Добавка про 15 минут и проверка на конфликты каждый раз
                    // Узнать у натальи, ставим ли мы упааковку после текущей или вместо (скорее всего первое)
                    //}
                }
            }
            return true;
            
        }
    }

    public function checkPlans($line_id, $prod_id, $start, $end)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        // Получаем планы, которые внутри нового
        $plans = ProductsPlan::where('line_id', '=', $line_id)
            ->where('date', $date)
            ->where('started_at', '<=', $start)
            ->where('ended_at', '>=', $start)
            ->orderBy('started_at', 'ASC')
            ->get()
            ->toArray();
        if (count($plans) > 1) {
            $plans = array_filter($plans, function ($item) use ($prod_id) {
                return $item['plan_product_id'] != $prod_id;
            });
            $planOne = $plans[0];
            // Если есть, то ставим новую ГП после той, внутрь которой попадаем и ставим новые ГП
            $diff = Carbon::parse($planOne['ended_at'])->diffInMinutes(Carbon::parse($start));
            $pl = ProductsPlan::where('plan_product_id', '=', $prod_id)->first();
            $pl->started_at = $planOne['ended_at'];
            $pl->ended_at = Carbon::parse($end)->addMinutes($diff)->format('H:i:s');
            $pl->save();
            $plans = ProductsPlan::where('line_id', '=', $line_id)
                ->where('started_at', '>=', $start)
                ->where('date', $date)
                ->where('plan_product_id', '!=', $prod_id)
                ->orderBy('started_at', 'ASC')
                ->get();
            $end = $pl->ended_at;
            foreach ($plans as $plan) {
                // Те, которые надо сдвинуть
                $plan->started_at = $end;
                $end = Carbon::parse($plan->ended_at)->addMinutes($diff)->format('H:i:s');
                $plan->ended_at = $end;
                $plan->save();
            }
        }
    }

    public function checkPackPlans($line_id, $prod_id, $start, $end)
    {
        ///??????
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $lastPlan = ProductsPlan::where('line_id', '=', $line_id)
            ->where('plan_product_id', '!=', $prod_id)
            ->where('ended_at', '>=', $start)
            ->where('date', $date)
            ->orderBy('ended_at', 'DESC')
            ->first()->get();
        if ($lastPlan) {
            $plan = ProductsPlan::where('plan_product_id', '=', $prod_id)->get();
            $diff = Carbon::parse($plan->started_at)->diff(Carbon::parse($lastPlan->ended_at));
            $plan->started_at = $lastPlan->ended_at;
            $plan->ended_at = Carbon::parse($plan->started_at)->addMinutes($diff)->format('H:i:s');
            $plan->save();
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
        if ($data = $request->post()) {
            $plan = ProductsPlan::find($data['plan_product_id']);
            $plan->started_at = Carbon::parse($plan->started_at)->addMinutes($data['diff']);
            $plan->ended_at = Carbon::parse($plan->ended_at)->addMinutes($data['diff']);

            $plan->save();
            return;
        }
        return -1;
    }

    public static function clear()
    {
        ProductsPlan::truncate();
        $def = LinesController::getDefaults();
        foreach ($def as $d) {
            $line = Lines::where('line_id', '=', $d['line_id'])->first()->get();
            if ($line) {
                $time = explode('-', $d['time']);
                // $line->started_at = $time[0];
                // $line->ended_at = $time[1];
                // $line->prep_time = $d['prep'];
                // $line->after_time = $d['end'];
                // $line->workers_count = $d['people'];
                $line->title = $d['title'];
                // $line->master = null;
                // $line->engineer = null;
                // $line->cancel_reason = null;
                $line->save();
                // LinesExtraController::delete
            }
        }
        LogsController::clear();
        SlotsController::clear();
        return;
    }

    public static function afterLineUpdate($line_id, $newStart, $oldStart, $newEnd, $oldEnd)
    {
        $date = session('date') ?? (new \DateTime())->format('Y-m-d');
        $slots = ProductsPlan::where('line_id', '=', $line_id)
            ->where('started_at', '=', $oldStart)
            ->where('date', $date)
            ->get();
        foreach ($slots as $slot) {
            $slot->started_at = $newStart;
            $slot->save();
        }

        $slots = ProductsPlan::where('line_id', '=', $line_id)
            ->where('ended_at', '=', $oldEnd)
            ->where('date', $date)
            ->get();
        foreach ($slots as $slot) {
            $slot->ended_at = $newEnd;
            $slot->save();
        }
    }
}
