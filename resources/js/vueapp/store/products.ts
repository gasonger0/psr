import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { CategoryInfo, useCategoriesStore } from "./categories";
import { deleteRequest, getRequest, postRequest, putRequest } from "../functions";
import { AxiosResponse } from "axios";

export type ProductOrder = {
    order_id: number,
    product_id: number,
    amount: number
};

export type ProductInfo = {
    product_id?: number,
    title: string,
    category_id?: number,
    amount2parts: string,
    parts2kg: string,
    kg2boil: string,
    cars: string,
    cars2plates: string,
    always_show?: boolean,
    category: CategoryInfo,
    order?: ProductOrder, //TODO полдумать о размещении заказов
    isEditing: boolean,
    errors?: number,
    hide: boolean
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
            el.hide = [1,3].includes(el.category.type_id.value) ? true : false;
            return el;
        });
        console.log(products.value);
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
            always_show: false,
            isEditing: true,
        };
        products.value.push(newProduct as ProductInfo);
        return newProduct as ProductInfo;
    }

    function hide(type_id: number) {
        products.value.forEach((el: ProductInfo) => {
            console.log(el.hide);
            if ([type_id, 3].includes(el.category.type_id.value)) {
                el.hide = true;
            } else {
                el.hide = false;
            }
        });
    }

    function fillOrders(orders: Array<ProductOrder>) {
        orders.forEach((el: ProductOrder) => {
            let product = getByID(el.product_id);
            if (product) {
                product.order = el;
            }
        });
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
        hide,
        fillOrders,
        add
    }
});