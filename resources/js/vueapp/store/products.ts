import { defineStore } from "pinia";
import { Ref, ref } from "vue";

export type ProductInfo = {
    product_id?: number,
    title: string,
    category_id: number,
    amount2parts: string,
    parts2kg: string,
    kg2boil: string,
    cars: string,
    cars2plates: string,
    always_show?: Ref<boolean>
};

export const useProductsStore = defineStore('products', () => {
    const products = ref<ProductInfo[]>([]);

    /****** API ******/
    async function _load(): Promise<void> {

    }

    async function _create(): Promise<void> {

    }

    async function _update(): Promise<void> {

    }

    async function _delete(): Promise<void> {

    }



});