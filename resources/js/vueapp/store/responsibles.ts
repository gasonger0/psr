import { defineStore } from "pinia";
import { deleteRequest, getRequest, notify, postRequest, putRequest } from "../functions";
import { AxiosError, AxiosResponse } from "axios";
import { objectType } from "ant-design-vue/es/_util/type";
import { positions } from "./dicts";
import { reactive, ref, Ref } from "vue";

export type ResponsibleInfo = {
    responsible_id?: number,
    title: string,
    position: number,
    isEditing: boolean
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
            el.isEditing = false;
            return el as ResponsibleInfo;
        });
    };
    /**
     * Загружает нового сотрудника в БД
     * @param {WorkerInfo} fields набор полей нового сотрудника 
     */
    async function _create(rec: ResponsibleInfo): Promise<void> {
        return await postRequest('/api/responsibles/create',
            rec,
            (r: AxiosResponse) => {
                rec.responsible_id = r.data.responsible_id;
                return true;
            },
            (err: AxiosError) => {
                notify('warning', err.message);
                return false;
            }
        )
    }
    /**
     * Обновляет данные об ответственном в БД
     * @param {ResponsibleInfo} rec  
     */
    async function _update(rec: ResponsibleInfo): Promise<void> {
        return await putRequest('/api/responsibles/update', rec);
    }

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
            isEditing: true
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

    function getByID(id: number): ResponsibleInfo | undefined {
        return responsibles.value.find((el: ResponsibleInfo) => el.responsible_id == id);
    }

    return { responsibles, _load, _create, _update, _delete, add, splice, getByID }
});