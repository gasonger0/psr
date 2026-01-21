import { defineStore } from "pinia";
import { deleteRequest, getRequest, notify, postRequest, putRequest } from "../functions";
import { AxiosError, AxiosResponse } from "axios";
import { ref, Ref } from "vue";

export type ResponsibleInfo = {
    responsible_id?: number,
    title: string,
    position: Ref<string>,
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
            // el.position = {
            //     position_id: el.position,
            //     title: positions[el.position]
            // };
            el.isEditing = false;
            el.position = ref(String(el.position));
            return el as ResponsibleInfo;
        });
    };
    /**
     * Загружает нового ответственного в БД
     * @param {ResponsibleInfo} fields набор полей нового сотрудника 
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
            splice(rec.responsible_id);
        }
        return;
    };


    /*---------LOCAL---------*/

    /**
     * Добавить сотрудника на фронт (локально)
     */
    function add(): void {
        responsibles.value.push({
            title: '',
            position: ref("1"),
            isEditing: true
        });
    };
    /**
     * Удалить ответственного из локального хранилища
     * @param id Идентификатор ответственного
     * @returns 
     */
    function splice(id: number): void {
        responsibles.value = responsibles.value.filter((n: ResponsibleInfo) => n.responsible_id != id);
        return;
    };

    function getByID(id: number): ResponsibleInfo | undefined {
        return responsibles.value.find((el: ResponsibleInfo) => el.responsible_id == id);
    }

    return { responsibles, _load, _create, _update, _delete, add, splice, getByID }
});