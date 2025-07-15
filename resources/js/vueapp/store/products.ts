import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { CategoryInfo, useCategoriesStore } from "./categories";
import { getRequest } from "../functions";


export type ProductInfo = {
    product_id?: number,
    title: string,
    category_id: number,
    amount2parts: string,
    parts2kg: string,
    kg2boil: string,
    cars: string,
    cars2plates: string,
    always_show?: Ref<boolean>,
    category: CategoryInfo
};

export const useProductsStore = defineStore('products', () => {
    const products: Ref<ProductInfo[]> = ref([]);

    /****** API ******/
    async function _load(): Promise<void> {
        const catStore = useCategoriesStore();
        products.value = (await getRequest('/api/products/get')).map((el: ProductInfo) => {
            el.category = catStore.getByID(el.category_id);
            return el;
        })
    }

    async function _create(): Promise<void> {

    }

    async function _update(): Promise<void> {

    }

    async function _delete(): Promise<void> {

    }

    return {
        products,
        _load,
        _create,
        _delete,
        _update
    }
});