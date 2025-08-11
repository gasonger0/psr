import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import { deleteRequest, getRequest, postRequest, putRequest } from "../functions";
import { AxiosResponse } from "axios";
import { ProductInfo } from "./products";

/**
 * Структура слота продукции
 */
export type ProductSlot = {
    product_slot_id?: number,
    product_id: number,
    line_id: number,
    people_count: number,
    perfomance: number,
    type_id: number,
    hardware: number,
    isEditing?: boolean
};

export type SlotsByStages = {
    1: ProductSlot[],
    2: ProductSlot[]
};
export const useProductsSlotsStore = defineStore('productsSlots', () => {
    const slots: Ref<ProductSlot[]> = ref([]);

    /****** API ******/
    /**
     * Загружает данные в хранилку
     */
    async function _load(): Promise<void> {
        slots.value = await getRequest('/api/products_slots/get')
    }

    /**
     * Создаёт новый слот продукции
     * @param slot Слот
     */
    async function _create(slot: ProductSlot): Promise<void> {
        await postRequest('/api/products_slots/create', slot,
            (r: AxiosResponse) => {
                slot.product_id = JSON.parse(r.data).product_slot_id;
            }
        )
    }

    /**
     * Обновляет слот продукции
     * @param slot 
     */
    async function _update(slot: ProductSlot): Promise<void> {
        await putRequest('/api/products_slots/update', slot);
    }

    async function _delete(slot: ProductSlot): Promise<void> {
        await deleteRequest('/api/products_slots/delete', slot,
            () => splice(slot.product_slot_id)
        )
    }

    /****** LOCAL ******/
    function getByTypeAndProductID(product_id: number, type_id: number): ProductSlot[] {
        return slots.value.filter((el: ProductSlot) => el.product_id == product_id && el.type_id == type_id)!;
    }

    function getByLineId(line_id: number) {
        return slots.value.filter((slot: ProductSlot) => slot.line_id == line_id);
    }

    function getById(slot_id: number) {
        return slots.value.find((el: ProductSlot) => el.product_slot_id == slot_id);
    }

    function add(product: ProductInfo, type_id: number, line_id: number): ProductSlot {
        let newSlot = {
            product_id: product.product_id,
            line_id: line_id,
            people_count: 0,
            perfomance: 0,
            type_id: type_id,
            hardware: 1,
            isEditing: true
        } as ProductSlot;
        slots.value.push(newSlot);
        return newSlot;
    }

    function splice(id: number): void {
        slots.value = slots.value.filter((n: ProductSlot) => n.product_slot_id != id);
        return;
    };

    return { 
        slots, 
        _load, 
        _create,
        _update,
        _delete,
        add,
        getByTypeAndProductID,
        getByLineId,
        getById
    };
});