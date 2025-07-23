import { deleteRequest, getRequest, putRequest } from "@/functions";
import * as dayjs from "dayjs"
import { defineStore } from "pinia"
import { ref, Ref } from "vue";
import { ProductSlot, useProductsSlotsStore } from "./productsSlots";
import { AxiosResponse } from "axios";
import { CheckboxValueType } from "ant-design-vue/es/checkbox/interface";
import { useProductsStore } from "./products";

export type ProductPlan = {
    plan_product_id?: number,
    slot_id: number,
    started_at: dayjs.Dayjs,
    ended_at: dayjs.Dayjs,
    amount: number,
    workers_count?: number,  // TODO deprecated
    hardware?: number,       // TODO тоже в слот пихаем
    colon?: Ref<number>,      // Или переделать в 1,2,3 как 1, 2, 1+2
    type_id?: number,        // TODO deprecated
    date?: dayjs.Dayjs,
    position: number,
    isDay?: boolean,
    delay?: number           // TODO добавить в БД
}

export const usePlansStore = defineStore("productsPlans", () => {
    const plans: Ref<ProductPlan[]> = ref([]);

    /***** API *****/
    async function _load() {
        plans.value = (await getRequest('/api/plans/get')).map(el => {
            // el.started_at = dayjs.default(el.started_at) 
            // TODO ?
            return el as ProductPlan;
        });
    }

    async function _delete(plan: ProductPlan) {
        await deleteRequest('/api/plans/delete', plan,
            (r: AxiosResponse) => {
                splice(plan.plan_product_id);
            }
        )
    }

    async function _change(data: ProductPlan[]) {
        putRequest('/api/plans/change', data.map(el => {
            return {
                plan_product_id: el.plan_product_id,
                started_at: el.started_at.format('HH:mm:ss'),
                ended_at: el.ended_at.format("HH:mm:ss"),
                position: el.position
            }
        }));
    }

    async function _create(data) {
        // TODO Дописать АПИ? чтобы возвращал ИД созданного плана, созданные планы продукции и обновлённые позиции пданов на других линиях
    }

    /***** LOCAL *****/
    function add(slot_id: number, started_at: dayjs.Dayjs, amount: number, position: number) {
        plans.value.push({
            slot_id: slot_id,
            started_at: started_at,
            ended_at: dayjs.default(),
            amount: amount,
            position: position,
            delay: 30
        });
        return plans.value.at(-1);
    }
    function getByLine(line_id: number): ProductPlan[] {
        let slots = useProductsSlotsStore().getByLineId(line_id).map(
            (el: ProductSlot) => { return el.product_id }
        );
        return plans.value.filter((plan: ProductPlan) => plan.slot_id in slots);
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
            return accum += curVal.type_id == stage_id ? curVal.amount : 0
        }, 0);
    }
    function splice(id: number): void {
        plans.value = plans.value.filter((n: ProductPlan) => n.plan_product_id != id);
        return;
    }

    return {
        plans,
        _load,
        _delete,
        _change,
        add,
        getByLine,
        getActiveSlots,
        getAmountFact
    }
});