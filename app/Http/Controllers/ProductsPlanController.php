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
    /**
     * Функция получения плана. Если в запросе в есть product_Id, по которому нужно получить данные - 
     * возвращаются только планы по этому продукту. Иначе все планы по дню и смене из куки 
     */
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

    /**
     * Функция добавления плана в параметрах запроса может быть указан план по варке и планы по упаковке
     */
    public function addPlan(Request $request)
    {
        // Получаем текущие день и смену
        $date = $request->cookie('date');
        $isDay = boolval($request->cookie('isDay'));
        
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
                    ->each(function($p) use($post, $date, $isDay) {
                        // Обновлчем объём изготовления
                        $p->amount = $post['amount'];
                        // Получаем справочныее данные о продукции
                        $prod = ProductsDictionary::where('product_id', '=', $p->product_id)->first();

                        // Вычисляем длительность изготовления по формулам из справочных данных
                        $duration = ceil($post['amount'] * 
                            eval("return ".$prod['parts2kg']."*".$post['amount'].";") * 
                            eval("return ".$prod['amount2parts'].";") / 
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
                        'hardware' => $post['hardware'],
                        'type_id' => 2,
                    ]);

                    // Считаем начало упаковки, как начало варки + задержка
                    $start = (new \DateTime($post['started_at']))
                        ->add(new \DateInterval('PT' . $post['delay'] . 'M'));
                    // Не помню, нахера так, но раз написано, значит, надо было
                    $plan->started_at = strval($start->format('H:i:s'));
                    // Рассчитываем, сколько будет упаковываться продукция по формулам
                    $duration = ceil($post['amount'] * 
                        eval("return " . $product['parts2kg'] . ";") /
                        // eval("return " . $product['amount2parts'] . ";") / 
                        ($slot->perfomance ? $slot->perfomance : 1) * 60
                    );
                    // считаем время от начала + время изготовления
                    $start->add(new \DateInterval('PT' . $duration . 'M'));
                    // +15 минут на упаковку
                    $start->add(new \DateInterval('PT15M'));       
                    
                    /*--
                    Этого финта я вообще не помню, нахуя он нужен был. Была речь о том, что типа упаковка не должна заканчиваться раньше, чем варка, но это не то
                    */
                    // $endPrev = (new \DateTime($post['ended_at']))->add(new \DateInterval('PT15M'))->add(new \DateInterval('PT' . $duration . 'M'));
                    
                    // Конец упаковки должен быть:
                    // 1. Не раньше конца варки
                    // 2. Если раньше конца варки, то не раньше конца варки + delay
                    $endPrev = new \DateTime($post['ended_at']);
                    if ($start < $endPrev) {
                        $plan->ended_at = $endPrev->add(new \DateInterval('PT' . $post['delay'] . 'M'))->format('H:i:s');
                    }else {
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

                    foreach ($positions as $pos){
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
                            if($d2['start'] < $dates['end'] || ($dates['start'] < $d2['start'] && $d2['start'] < $dates['end'])) {
                                $position = $pos->position+1;
                            }
                        } else {
                            // Сюда падаем, если позиция уже посчитана, двигаем всё, что ниже неё вниз на 1
                            $pos->position+=1;
                            $pos->save();
                        }
                    }

                    // Если фолс, значит она первая
                    if ($position === FALSE) {
                        $position = 0;
                        $positions->each(function ($pos) use ($position) {
                            $pos->position+=1;
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

    public static function checkPlans($date, $isDay, $plan, $position = null) {

        if (!$position) {
            $position = $plan->position;
        }
        // Проверка, не ставим ли мы план первым
        $check = ProductsPlan::where('line_id', '=', $plan->line_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            // Мб надо добавить проверку на позицию?
            ->count();
        var_dump($check);
        if ($check == 0) {
            var_dump("Plan is first and only");
            return;
        }   
        var_dump(ProductsPlan::where('line_id', $plan->line_id)->where('isDay', $isDay)->where('date', $date)->get()->toArray());
        // Проверка - залезаем ли мы началом новой ГП на окончание предыдущей
        $upPlan = ProductsPlan::where('position', '<=', $position)
            ->where('ended_at', '>=', $plan->started_at)
            ->where('started_at', '<=', $plan->started_at) 
            ->where('line_id', '=', $plan->line_id)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->orderBy('position', 'DESC')
            ->first();
        // Проверка - залезаем ли мы концом новой ГП на начало следующей
        $downPlan = ProductsPlan::where('position', '>=', $position)
            ->where('started_at', '<=', $plan->ended_at)
            ->where('ended_at', '>=', $plan->ended_at)
            ->where('line_id', '=', $plan->line_id)
            ->where('plan_product_id', '!=', $plan->plan_product_id)
            ->where('date', $date)
            ->where('isDay', $isDay)
            ->orderBy('position', 'ASC')
            ->first();

        if ($plan->started_at > $plan->ended_at) {
            // Пограничная позиция, надо сделать доп. проверку
            // Т.к. позиция пограничная, то сдвиг сверху остаётся, а вот сдвиг снизу надо считать иначе
            $change = ProductsPlan::where('position', '>=', $position)
                // ->where('started_at', '>', '20:00:00')
                ->where('line_id', '=', $plan->line_id)
                ->where('plan_product_id', '!=', $plan->plan_product_id)
                ->where('date', $date)
                ->where('isDay', $isDay)
                ->orderBy('ended_at', 'ASC')
                ->first();
            if ($change && $change->position >= $position) {
                $downPlan = $change;
            }
        }


        if ($upPlan || $downPlan) {
            $topShift = $upPlan ? Carbon::parse($plan->started_at)->diffInMinutes(Carbon::parse($upPlan->ended_at)) : 0;
            $downShift = $downPlan ? Carbon::parse($downPlan->started_at)->diffInMinutes(Carbon::parse($plan->ended_at)) : 0;
            if ($plan->started_at > $plan->ended_at) {
                $downShift = $downPlan ? Carbon::parse($downPlan->started_at)->diffInMinutes(Carbon::parse($plan->ended_at)->add('P1D')) : 0;
            }
            var_dump("topShift: $topShift, downShift: $downShift");
            $shift = $topShift + $downShift;
            if ($shift != 0) {
                // Тут двигаем только вслучае, если позиция новой ГП ненулевая потому что нет смысла двигать нулевую - она и так никуда не залезает 
                $p = ProductsPlan::where('plan_product_id', $plan->plan_product_id)
                    ->where('date', $date)
                    ->where('isDay', $isDay)
                    ->where('line_id', $plan->line_id)
                    ->where('position', '>', '0')
                    ->first();
                if ($p){
                    $p->update([
                        'started_at' => Carbon::parse($plan->started_at)->addMinutes($topShift)->format('H:i:s'),
                        'ended_at' => Carbon::parse($plan->ended_at)->addMinutes($topShift)->format('H:i:s')
                    ]);
                }
                ProductsPlan::where('position', '>=', $position)
                    // ->where('started_at', '>', $plan->started_at)
                    ->where('line_id', '=', $plan->line_id)
                    ->where('plan_product_id', '!=', $plan->plan_product_id)
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
                ->where('plan_product_id', '!=', $plan->plan_product_id)
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
        }

        $plans = ProductsPlan::where('line_id', '=', $plan->line_id)
            ->where('date', $request->cookie('date'))
            ->where('isDay', $request->cookie('isDay'))
            ->orderBy('position', 'ASC')
            ->get()
            ->toArray();
        if ($plans) {
            $minStartedAt = $plans[0]['started_at'];
            $maxEndedAt = end($plans)['ended_at'];
            LinesExtraController::update(
                $request->cookie('date'), 
                $request->cookie('isDay'), 
                $plan->line_id, 
                ['started_at' => $minStartedAt, 'ended_at' => $maxEndedAt]
            );
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
                self::checkPlans($date, $isDay, $prod,$item['position']);
                $packs = ProductsPlan::where('product_id', '=', $prod->product_id)
                    ->where('type_id', '=', 2)
                    ->where('date', '=', $date)
                    ->where('isDay', '=', $isDay)
                    ->get();
                if ($packs) {
                    foreach ($packs as $pack) {
                        $start_diff = Carbon::parse($pack->started_at)->diffInMinutes(Carbon::parse($old_start));
                        $duration = abs(Carbon::parse($pack->ended_at)->diffInMinutes(Carbon::parse($pack->started_at)));
                        $pack->started_at = Carbon::parse($pack->started_at)->addMinutes($start_diff)->format('H:i:s');
                        $pack->ended_at = Carbon::parse($pack->started_at)->addMinutes($duration)->format('H:i:s');
                        $pack->save();
                        self::checkPlans($date, $isDay, $pack);
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
}
