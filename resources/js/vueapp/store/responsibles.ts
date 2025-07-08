import { defineStore } from "pinia";
import { getRequest, postRequest } from "../functions";
import { AxiosResponse } from "axios";
import { objectType } from "ant-design-vue/es/_util/type";
import { positions } from "./dicts";
import { reactive } from "vue";

export type ResponsibleInfo = {
    responsible_id?: number,
    title: string,
    position: number
};

export const useResponsiblesStore = defineStore('responsible', () => {
    let responsibles: ResponsibleInfo[] = reactive([]);
    /*----------API----------*/

    /**
     * Загрузить список в хранилку
     * @returns {void}
     */
    async function _load(): Promise<void> {
        responsibles = await getRequest('api/get_responsible');
        responsibles.map((el: any) => {
            el.position = {
                position_id: el.position,
                title: positions[el.position]
            };
            return el as ResponsibleInfo;
        });
    };
    /**
     * Удалить ответственного из реестра
     * @param rec Данные ответственного
     * @returns 
     */
    async function _delete(rec: ResponsibleInfo): Promise<void> {
        let res = await postRequest('/api/responsible/delete', rec)
        if (res) {
            this.responsibles.splice(rec.responsible_id);
        }
        return;
    };


    /*---------LOCAL---------*/

    /**
     * Добавить сотрудника на фронтх (локально)
     */
    function add(): void {
        this.responsibles.push({
            // TODO
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
        return responsibles.find((el: ResponsibleInfo) => el.responsible_id == id);
    }

    return { responsibles, _load, _delete, add, splice, getById }
});