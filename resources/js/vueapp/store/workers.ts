import { defineStore } from "pinia";
import dayjs from 'dayjs';
import { deleteRequest, getRequest, getTimeString, notify, postRequest, putRequest, SelectOption } from "../functions";
import { AxiosError, AxiosResponse } from "axios";
import { Slot } from "./dicts";
import { computed, reactive, Ref, ref } from "vue";
// TODO проверить АПИ-роуты и перенеси подгрузку слотов в отдельную хранилку мейби?

// Интерфейсы

// Информация о сотруднике
export type WorkerInfo = {
    title: string,
    break?: Slot,
    worker_id?: number,
    current_line_id?: number | null,
    current_slot_id?: number,
    company?: string,
    on_break?: boolean,
    popover?: boolean,
    isEdited?: Ref<boolean>
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
        name: 'company',
        label: 'Компания',
        rules: [{
            required: true,
            message: 'Заполните компанию сотрудника. Если это штатский сотрудник, пишите "Сокол"'
        }]
    }
};

export const useWorkersStore = defineStore('workers', () => {
    const workers: Ref<WorkerInfo[]> = ref([]);

    const calcBreak = (worker: WorkerInfo) => computed(() => worker.on_break ? 'break' : '');

    /*----------API----------*/
    /**
     *  Загрузить данные в хранилку с бэка
     */
    async function _load(): Promise<void> {
        const items = await getRequest('/api/workers/get');
        workers.value = items.map((worker: any): WorkerInfo => {
            worker.isEdited = ref(false);
            return serialize(worker);
        });
    };
    /**
     * Загружает нового сотрудника в БД
     * @param {WorkerInfo} fields набор полей нового сотрудника 
     */
    async function _create(fields: WorkerInfo): Promise<boolean> {
        return await postRequest('/api/worker/create',
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
     * @param fields 
     */
    async function _update(fields: WorkerInfo): Promise<boolean> {
        return await putRequest('/api/worker/update', fields);
    }
    /**
     * Удаляет сотрудника из БД
     * @param worker 
     */
    async function _delete(worker: WorkerInfo): Promise<boolean> {
        return await deleteRequest('/api/worker/delete', worker,
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
        fields.break = {
            started_at: dayjs(fields.break_started_at, 'HH:mm:ss'),
            ended_at: dayjs(fields.break_ended_at, 'HH:mm:ss')
        };
        delete fields.break_started_at;
        delete fields.break_ended_at;

        // TODO: Хз, сработает ли тренарно
        if (fields.break.started_at <= getTimeString() <= fields.break.ended_at) {
            fields.onBreak = true;
        }

        fields.popover = false;
        return fields as WorkerInfo;
    };
    /**
     * Добавляет новго пустого сотружника в хранилку
     */
    function add(): void {
        workers.value.push({
            title: '',
            company: ''
        });
        return;
    };
    /**
     * Получить сотрудника по ID
     * @param worker_id 
     * @returns 
     */
    function getById(worker_id: number): WorkerInfo | undefined {
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
        getById,
        getByLine,
        toSelectOptions
    };
});
