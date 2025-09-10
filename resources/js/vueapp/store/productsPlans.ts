import { deleteRequest, getRequest, postRequest, putRequest } from "@/functions";
import * as dayjs from "dayjs"
import { defineStore } from "pinia"
import { ref, Ref } from "vue";
import { ProductSlot, useProductsSlotsStore } from "./productsSlots";
import { AxiosResponse } from "axios";
import { CheckboxValueType } from "ant-design-vue/es/checkbox/interface";
import { useProductsStore } from "./products";
import { format } from "./dicts";
import { LineInfo, useLinesStore } from "./lines";
import { RefSymbol } from "@vue/reactivity";
import { useModalsStore } from "./modal";

export type ProductPlan = {
    plan_product_id?: number,
    slot_id: number,
    started_at: dayjs.Dayjs,
    ended_at: dayjs.Dayjs,
    amount: number,
    colon?: Ref<number>,
    date?: dayjs.Dayjs,
    isDay?: boolean,
    parent?: number,
    delay?: number
}

export const usePlansStore = defineStore("productsPlans", () => {
    const plans: Ref<ProductPlan[]> = ref([]);

    /***** API *****/
    async function _load() {
        plans.value = (await getRequest('/api/plans/get')).map(el => {
            return serialize(el);
        });
    }

    async function _delete(plan: ProductPlan) {
        await deleteRequest('/api/plans/delete', plan,
            (r: AxiosResponse) => {
                splice(plan.plan_product_id);
                plans.value.filter(el => el.parent == plan.plan_product_id).forEach(i => splice(i.plan_product_id));
            }
        )
    }

    async function _change(data: ProductPlan[]) {
        putRequest('/api/plans/change', data.map(el => {
            return {
                plan_product_id: el.plan_product_id,
                started_at: el.started_at.format(format),
                ended_at: el.ended_at.format(format),
            }
        }));
    }

    async function _create(data: ProductPlan, packs: Array<number>) {
        await postRequest('/api/plans/create', unserialize(data, packs),
            (r: AxiosResponse) => {
                data.plan_product_id = r.data.plan_product_id;
                
                let line = useLinesStore().getByID(
                    useProductsSlotsStore().getById(Number(data.slot_id)).line_id
                );
                line.has_plans = true;
                if (r.data.packs) {
                    r.data.packs.forEach((pack: any) => {
                        plans.value.push(serialize(pack));
                    });
                }
                if (r.data.plansOrder) {
                    for (let i in r.data.plansOrder) {
                        line = useLinesStore().getByID(Number(i));
                        line.has_plans = true;
                        updateTimes(r.data.plansOrder[i]);
                        useLinesStore().updateVersion(line.line_id);                        
                    }
                }
            }
        );
    }

    async function _update(data: ProductPlan, packs: Array<number>) {
        await putRequest('/api/plans/update', unserialize(data, packs),
            (r: AxiosResponse) => {
                if (r.data.plansOrder) {
                    for (let i in r.data.plansOrder) {
                        let line = useLinesStore().getByID(Number(i));
                        line.has_plans = true;
                        updateTimes(r.data.plansOrder[i]);
                        useLinesStore().updateVersion(line.line_id);                        
                    }
                }
            });
    }

    async function _clear() {
        await deleteRequest('/api/plans/clear', {},
            (r: AxiosResponse) => {
                plans.value = [];
                useLinesStore().lines.forEach((el: LineInfo) => {
                    el.has_plans = false;
                });
            }
        )
    }

    /***** LOCAL *****/
    function add(slot_id: number, started_at: dayjs.Dayjs, amount: number) {
        plans.value.push({
            slot_id: slot_id,
            started_at: started_at,
            ended_at: dayjs.default(),
            amount: amount,
            delay: 0
        });
        return plans.value.at(-1);
    }
    function getByLine(line_id: number): ProductPlan[] {
        let slots = useProductsSlotsStore().getByLineId(line_id).map(
            (el: ProductSlot) => { return el.product_slot_id }
        );
        return plans.value.filter((plan: ProductPlan) => slots.includes(plan.slot_id)).sort(
            (a, b) => {
                return a.started_at.unix() - b.started_at.unix()
            });
    }
    function updateTimes(data: Array<any>) {
        data.forEach(el => {
            // Пробуем объём и слот вместо ИД, чтобы избежать задвоения
            let pl = plans.value.find((i: ProductPlan) => i.amount == el.amount && i.slot_id == el.slot_id);
            let index = plans.value.indexOf(pl);
            if (index != -1) {
                plans.value[index] = serialize(el);
            } else {
                plans.value.push(serialize(el));
            }
            // pl = serialize(el);÷
            // TODO обновлять линию походу надо
        });
    }
    function getActiveSlots(product_id: number): Array<number> {
        let ids = [];
        plans.value.forEach((el: ProductPlan) => {
            let product = useProductsSlotsStore().getById(el.slot_id);
            if (product.product_id == product_id) {
                ids.push(el.slot_id);
            }
        });
        return ids;
    }
    function getAmountFact(slot_ids: Array<number>, stage_id: number): number {
        let pls = plans.value.filter((el: ProductPlan) => slot_ids.includes(el.slot_id));
        return pls.reduce((accum, curVal) => {
            return accum += useProductsSlotsStore().getById(curVal.slot_id).type_id == stage_id ? curVal.amount : 0
        }, 0);
    }
    function splice(id: number): void {
        plans.value = plans.value.filter((n: ProductPlan) => n.plan_product_id != id);
        return;
    }
    function removeLast(): void {
        plans.value.splice(-1, 1);
    }
    function serialize(plan: any) {
        plan.started_at = dayjs.default(plan.started_at),
            plan.ended_at = dayjs.default(plan.ended_at),
            plan.colon = ref(plan.colon);
        return plan as ProductPlan;
    }
    function unserialize(plan: ProductPlan, packs: Array<number>) {
        let payload = Object.assign({}, plan as any);
        payload.packs = packs;
        payload.started_at = plan.started_at.format(format);
        payload.ended_at = plan.ended_at.format(format);

        return payload;
    }
    function getById(plan_id: number) {
        return plans.value.find(el => el.plan_product_id == plan_id);
    }


    return {
        plans,
        _load,
        _create,
        _update,
        _delete,
        _change,
        _clear,
        add,
        getByLine,
        getActiveSlots,
        getAmountFact,
        removeLast
    }
});