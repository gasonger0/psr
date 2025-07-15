import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import { getRequest } from "../functions";

export type CategoryInfo = {
    category_id?: number,
    title: string,
    type: Ref<boolean>
};

export const useCategoriesStore = defineStore('categories', () => {
    const categories: Ref<CategoryInfo[]> = ref([]);

    /****** API ******/
    async function _load(): Promise<void> {
        categories.value = await getRequest('/api/categories/get');
    }

    /****** LOCAL ******/
    function getByID(id: number): CategoryInfo {
        return categories.value.find((el: CategoryInfo) => el.category_id == id)!;
    }
    return { categories, _load, getByID };
});