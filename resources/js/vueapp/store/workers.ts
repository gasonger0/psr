import { defineStore } from "pinia";
import * as dayjs from 'dayjs';
import { deleteRequest, getRequest, getTimeString, notify, postRequest, putRequest, SelectOption } from "../functions";
import { AxiosError, AxiosResponse } from "axios";
import { format, Slot } from "./dicts";
import { computed, reactive, Ref, ref } from "vue";
import { CompanyInfo, useCompaniesStore } from "./companies";

// Информация о сотруднике
export type WorkerInfo = {
    title: string,
    break?: Slot,
    worker_id?: number,
    current_line_id?: number | null,
    current_slot_id?: number,
    company?: CompanyInfo,
    on_break?: boolean,
    popover?: boolean,
    isEditing: boolean
};

// Форма для нового сотрудника в справочнике/веб-интерфейсе
export const WorkerForm = {
    title: {
        name: 'title',
        label: 'ФИО',
        rules: [{
            required: true,
            message: 'Заполните ФИО сотрудника'
        }]
    },
    company: {
        name: 'company_id',
        label: 'Компания',
        rules: [{
            required: true,
            message: 'Заполните компанию сотрудника. Если это штатский сотрудник, пишите "Сокол"'
        }]
    }
};

export const useWorkersStore = defineStore('workers', () => {
    const workers: Ref<WorkerInfo[]> = ref([]);

    const calcBreak = (worker: WorkerInfo) => computed(() => 
        worker.break.started_at.format('HH:mm:ss') <= getTimeString().format('HH:mm:ss') 
        && 
        getTimeString().format('HH:mm:ss') <= worker.break.ended_at.format('HH:mm:ss')
    );

    /*----------API----------*/
    /**
     *  Загрузить данные в хранилку с бэка
     */
    async function _load(): Promise<void> {
        const items = await getRequest('/api/workers/get');
        workers.value = items.map((worker: any): WorkerInfo => {
            worker.isEditing = false;
            worker.on_break = calcBreak;
            return serialize(worker);
        });
    };
    /**
     * Загружает нового сотрудника в БД
     * @param {WorkerInfo} fields набор полей нового сотрудника 
     */
    async function _create(fields: WorkerInfo): Promise<boolean> {
        return await postRequest('/api/workers/create',
            fields,
            (r: AxiosResponse) => {
                fields.worker_id = r.data.worker_id;
                return true;
            },
            (err: AxiosError) => {
                notify('warning', err.message);
                return false;
            }
        )
    };
    /**
     * Обновляет данные о сотрдунике в БД
     * @param {WorkerInfo} fields 
     */
    async function _update(fields: WorkerInfo): Promise<boolean> {
        return await putRequest('/api/workers/update', unserialize(fields));
    }
    /**
     * Удаляет сотрудника из БД
     * @param worker 
     */
    async function _delete(worker: WorkerInfo): Promise<boolean> {
        return await deleteRequest('/api/workers/delete', unserialize(worker),
            (r: AxiosResponse) => {
                splice(worker.worker_id!);
            }
        );
    }
    /*--------- LOCAL ---------*/
    /**
     * Удалить сотрудника из локального хранилища
     * @param id идентификатор сотрудника
     * @returns {void}
     */
    function splice(id: number): void {
        workers.value = workers.value.filter((n: WorkerInfo) => n.worker_id != id);
    };
    /**
     * Обрабатывает загруженные в хранилище данные 
     * @param {any} fields  Поля одного сотрудника
     * @returns {WorkerInfo} массив обработанных полей сотрудника
     */
    function serialize(fields: any): WorkerInfo {
        fields.break.started_at = dayjs.default(fields.break.started_at, format);
        fields.break.ended_at = dayjs.default(fields.break.ended_at, format);
        fields.company = useCompaniesStore().getByID(fields.company_id);

        fields.popover = false;
        return fields as WorkerInfo;
    };
    function unserialize(worker: WorkerInfo): Object {
        let payload = JSON.parse(JSON.stringify(worker));
        payload.break.started_at = worker.break.started_at.format(format);
        payload.break.ended_at = worker.break.ended_at.format(format);
        payload.company_id = payload.company.company_id;
        return payload;
    }
    /**
     * Добавляет новго пустого сотружника в хранилку
     */
    function add(): void {
        workers.value.push({
            title: '',
            company: useCompaniesStore().companies.at(0),
            isEditing: true
        });
        return;
    };
    /**
     * Получить сотрудника по ID
     * @param worker_id 
     * @returns 
     */
    function getByID(worker_id: number): WorkerInfo | undefined {
        return workers.value.find((el: WorkerInfo) => el.worker_id == worker_id);
    };
    /**
     * Получить список сотрудников на линии текущей смены
     * @param {number} line_id идентификатор линии
     * @returns {WorkerInfo[] | undefined} найденные сотрудник для данной линии 
     */
    function getByLine(line_id: number | null): WorkerInfo[] | undefined {
        return workers.value.filter((el: WorkerInfo) => el.current_line_id == line_id);
    };
    /**
     * Мапа для возврата массива для селектов
     * @returns {SelectOption[]} массив опций для компонента Select
     */
    function toSelectOptions(): SelectOption[] {
        return this.workers.map((el: WorkerInfo) => {
            return {
                key: el.worker_id,
                label: el.title,
                value: el.title
            }
        });
    };

    return {
        workers,
        calcBreak,
        _load,
        _create,
        _update,
        _delete,
        splice,
        serialize,
        add,
        getByID,
        getByLine,
        toSelectOptions
    };
});
