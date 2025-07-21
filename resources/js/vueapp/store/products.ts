import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { CategoryInfo, useCategoriesStore } from "./categories";
import { deleteRequest, getRequest, postRequest, putRequest } from "../functions";
import { AxiosResponse } from "axios";


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
    category: CategoryInfo,
    order: ProductOrder, //TODO полдумать о размещении заказов
    isEditing: boolean
};

export const useProductsStore = defineStore('products', () => {
    const products: Ref<ProductInfo[]> = ref([]);

    /****** API ******/
    async function _load(data: Object): Promise<void> {
        // TODO возможно чисто гетом можно
        const catStore = useCategoriesStore();
        products.value = (await postRequest('/api/products/get', data)).map((el: ProductInfo) => {
            el.category = catStore.getByID(el.category_id);
            el.isEditing = false;
            return el;
        });
    }

    async function _create(product: ProductInfo): Promise<void> {
        await postRequest('/api/products/create', product,
            (r: AxiosResponse) => {
                product.product_id = JSON.parse(r.data).product_id;
            }
        )
    }

    async function _update(product: ProductInfo): Promise<void> {
        await putRequest('/api/products/update', product);
    }

    async function _delete(product: ProductInfo): Promise<void> {
        await deleteRequest('/api/products/delete', product,
            () => splice(product.product_id)
        )
    }


    /***** LOCAL *****/
    function getByID(id: number): ProductInfo | undefined {
        return products.value.find((el: ProductInfo) => el.product_id == id);
    }

    function getByCategoryID(category_id: number): ProductInfo[] {
        return products.value.filter((el: ProductInfo) => el.category.category_id == category_id);
    }
    function add(category: CategoryInfo): ProductInfo {
        let newProduct = {
            title: '',
            amount2parts: '',
            parts2kg: '',
            kg2boil: '',
            cars: '',
            cars2plates: '',
            category: category,
            always_show: ref(false),
            isEditing: true
        };
        products.value.push(newProduct as ProductInfo);
        return newProduct as ProductInfo;
    }

    function getByType(type_id: number) {
        return products.value.filter((el: ProductInfo) => [type_id, 3].find(i => el.category.type.value == i));
    }

    function splice(id: number): void {
        products.value = products.value.filter((n: ProductInfo) => n.product_id != id);
        return;
    };

    return {
        products,
        _load,
        _create,
        _delete,
        _update,
        getByID,
        getByCategoryID,
        add
    }
});