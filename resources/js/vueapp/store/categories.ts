import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import { getRequest } from "../functions";
import { DataNode } from "ant-design-vue/es/tree";

export type CategoryInfo = {
    category_id?: number,
    title: string,
    key?: number,
    parent?: number,
    children?: CategoryInfo[],
    type_id: number
};

export const useCategoriesStore = defineStore('categories', () => {
    const categories: Ref<CategoryInfo[]> = ref([]);

    /****** API ******/
    async function _load(): Promise<void> {
        categories.value = (await getRequest('/api/categories/get')).map(el => {
            el.type_id = ref(el.type_id);
            return el as CategoryInfo;
        });
    }

    /****** LOCAL ******/
    function getByID(id: number): CategoryInfo {
        return categories.value.find((el: CategoryInfo) => el.category_id == id)!;
    }

    /**
     * Возвращает дерево категорий
     */
    function asTree(): DataNode[] {
        let tree = [];

        categories.value.filter((el: CategoryInfo) => el.parent == null).forEach((branch: CategoryInfo) => {
            branch.key = branch.category_id;
            branch.children = fillTree(categories.value, categories.value.filter((i: CategoryInfo) => i.parent == branch.category_id));
            tree.push(branch);
        });
        console.log(tree);
        return tree as DataNode[];
    }

    /** 
     * Возвращает заполненный лист дерева
     */
    function fillTree(data: CategoryInfo[], branches: CategoryInfo[]): CategoryInfo[] {
        let tree = [];
        branches.forEach((branch: CategoryInfo) => {
            branch.key = branch.category_id;
            branch.children = fillTree(data, data.filter((i: CategoryInfo) => i.parent == branch.category_id));
            tree.push(branch);
        });
        return tree;
    }

    return { categories, _load, getByID, asTree };
});