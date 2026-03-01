import { defineStore } from "pinia";
import { ref, Ref } from "vue";
import { deleteRequest, getRequest, notify, postRequest, putRequest } from "../functions";
import { DataNode } from "ant-design-vue/es/tree";
import { AxiosResponse } from "axios";

export type CategoryInfo = {
    category_id?: number,
    title: string,
    key?: number,
    parent_category?: number,
    children?: CategoryInfo[],
    type_id: number,
    isEditing?: boolean;
};

export const useCategoriesStore = defineStore('categories', () => {
    const categories: Ref<CategoryInfo[]> = ref([]);

    /****** API ******/
    async function _load(): Promise<void> {
        categories.value = (await getRequest('/api/categories/get')).map(el => {
            el.type_id = ref(el.type_id);
            el.isEditing = false;
            return el as CategoryInfo;
        });

    }

    async function _create(cat: CategoryInfo): Promise<void> {
        await postRequest('/api/categories/create', cat,
            (r: AxiosResponse) => {
                let data = r.data;
                data.type_id = ref(data.type_id);
                data.isEditing = false;
                categories.value.push(r.data);
                notify("success", "Категория добавлена");
            }
        )
    }

        async function _update(cat: CategoryInfo): Promise<void> {
            await putRequest('/api/categories/update', cat);
        }
    
        async function _delete(cat: CategoryInfo): Promise<void> {
            await deleteRequest('/api/categories/delete', cat,
                () => splice(cat.category_id)
            )
        }

    /****** LOCAL ******/
    function add(parent?: CategoryInfo): CategoryInfo {
        console.log(parent);
        const category = {
            parent_category: (parent ? parent.category_id : null),
            title: '',
            type_id: parent?.type_id ?? 1
        } as CategoryInfo;
        categories.value.push(category);
        return category;
    } 

    function getByID(id: number): CategoryInfo {
        return categories.value.find((el: CategoryInfo) => el.category_id == id)!;
    }

    /**
     * Возвращает дерево категорий
     */
    function asTree(): DataNode[] {
        let tree = [];

        categories.value.filter((el: CategoryInfo) => el.parent_category == null).forEach((branch: CategoryInfo) => {
            branch.key = branch.category_id;
            branch.children = fillTree(categories.value, categories.value.filter((i: CategoryInfo) => i.parent_category == branch.category_id), branch.category_id);
            tree.push(branch);
        });
        console.log(tree);

        return tree as DataNode[];
    }

    /** 
     * Возвращает заполненный лист дерева
     */
    function fillTree(data: CategoryInfo[], branches: CategoryInfo[], parent?: number): any[] {
        let tree = [];
        branches.forEach((cop: CategoryInfo) => {
            let branch = cop as any;
            branch.key = branch.category_id;
            branch.children = fillTree(data, data.filter((i: CategoryInfo) => i.parent_category == branch.category_id), cop.category_id);
            branch.parent = parent;
            tree.push(branch);
        });
        return tree;
    }

        function splice(id: number): void {
            categories.value = categories.value.filter((n: CategoryInfo) => n.category_id != id);
            return;
        };

    return { categories, _load, 
        _create,
        _update,
        _delete,
        add,
        getByID, asTree };
});