import { defineStore } from "pinia";
import { deleteRequest, getRequest, postRequest } from "../functions";
import { AxiosResponse } from "axios";
import { objectType } from "ant-design-vue/es/_util/type";
import { positions } from "./dicts";
import { reactive, ref, Ref } from "vue";

export type ResponsibleInfo = {
    responsible_id?: number,
    title: string,
    position: number,
    isEdited?: Ref<boolean>
};

export const useResponsiblesStore = defineStore('responsible', () => {
    const responsibles: Ref<ResponsibleInfo[]> = ref([]);
    /*----------API----------*/

    /**
     * Загрузить список в хранилку
     * @returns {void}
     */
    async function _load(): Promise<void> {
        responsibles.value = (await getRequest('api/responsibles/get')).map((el: any) => {
            el.position = {
                position_id: el.position,
                title: positions[el.position]
            };
            el.isEdited = ref(false);
            return el as ResponsibleInfo;
        });
    };
    /**
     * Удалить ответственного из реестра
     * @param rec Данные ответственного
     * @returns 
     */
    async function _delete(rec: ResponsibleInfo): Promise<void> {
        let res = await deleteRequest('/api/responsibles/delete', rec)
        if (res) {
            this.responsibles.splice(rec.responsible_id);
        }
        return;
    };


    /*---------LOCAL---------*/

    /**
     * Добавить сотрудника на фронт (локально)
     */
    function add(): void {
        this.responsibles.push({
            title: '',
            position: ref(1),
            isEdited: ref(true)
        });
    };
    /**
     * Удалить ответственного из локального хранилища
     * @param id Идентификатор ответственного
     * @returns 
     */
    function splice(id: number): void {
        this.workers = this.workers.filter((n: ResponsibleInfo) => n.responsible_id != id);
        return;
    };

    function getById(id: number): ResponsibleInfo|undefined{
        return responsibles.value.find((el: ResponsibleInfo) => el.responsible_id == id);
    }

    return { responsibles, _load, _delete, add, splice, getById }
});