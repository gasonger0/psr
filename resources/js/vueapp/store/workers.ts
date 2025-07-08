import { defineStore } from "pinia";
import dayjs from 'dayjs';
import { getRequest, getTimeString, notify, postRequest, SelectOption } from "../functions";
import { AxiosError, AxiosResponse } from "axios";
import { Slot } from "./dicts";
import { computed, reactive } from "vue";
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
    popover?: boolean
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
    let workers: WorkerInfo[] = reactive([]);

    const calcBreak = (worker: WorkerInfo) => computed(() => worker.on_break ? 'break' : '');

    /*----------API----------*/
    /**
     *  Загрузить данные в хранилку с бэка
     */
    async function _load(): Promise<void> {
        const items = await getRequest('/api/get_workers');
        workers.values = items.map((worker: any): WorkerInfo => {
            // TODO добавить текуший слот и текущую линию?
            return serialize(worker);
        });
    };
    /**
     * Удалить работника со смены
     * @param rec Запись о слоте сотрудника
     * @param del 
     * // TODO имеет смысл перенести в другую хранилку (слотов)
     */
    async function _remove(rec: WorkerInfo, del: Boolean) {
        await postRequest('/api/delete_slot', {
            worker_id: rec.worker_id,
            slot_id: rec.current_slot_id,
            delete: del
        }, (response: AxiosResponse) => {
            splice(rec.worker_id!);
            notify('success', `Сотрудник ${rec.title} убран со смены`);
            return;
        },
            (err: AxiosError) => {
                notify('error', err.message);
                return;
            });
    };
    /**
     * Замена работника на смене
     * @param old_worker 
     * @param new_worker 
     * @returns 
     * // TODO перенести в хранилку слотов?
     */
    async function _changeWorker(old_worker_id: number, new_worker_id: number) {
        let old_worker = getById(old_worker_id);
        let new_worker = getById(new_worker_id);
        return await postRequest('/api/replace_worker', {
            old_worker_id: old_worker!.worker_id,
            slot_id: old_worker!.current_slot_id,
            new_worker_id: new_worker!.worker_id
        }, async (r: any) => {
            old_worker!.popover = false;
            splice(old_worker!.worker_id!);
            new_worker!.current_slot_id = r.slot_id;
            new_worker!.current_line_id = r.line_id;
            return true;
        }, (err: AxiosError) => {
            notify('error', err.message);
            return false;
        });
    };
    /**
     * Загружает нового сотрудника в БД
     * @param {WorkerInfo} fields набор полей нового сотрудника 
     */
    async function _addWorker(fields: WorkerInfo): Promise<boolean> {
        return await postRequest('/api/add_worker',
            fields,
            (r: AxiosResponse) => {
                // TODO: Поправить Апи, чтобы возвращал нового работника
                // this.workers.push(r as Worker); Работник уже в хранилке, его добавлять не надо, только ID присвоить
                return true;
            },
            (err: AxiosError) => {
                notify('warning', err.message);
                return false;
            }
        )
    };

    /*--------- LOCAL ---------*/
    /**
     * Удалить сотрудника из локального хранилища
     * @param id идентификатор сотрудника
     * @returns {void}
     */
    function splice(id: number): void {
        workers = workers.filter((n: WorkerInfo) => n.worker_id != id);
        return;
    };
    /**
     * Обрабатывает загруженные в хранилище данные 
     * @param {any} fields  Поля одного сотрудника
     * @returns {WorkerInfo} массив обработанных полей сотрудника
     */
    function serialize(fields: any): WorkerInfo {
        fields.break = {
            started_at: dayjs(fields.break_started_at),
            ended_at: dayjs(fields.break_ended_at)
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
        workers.push({
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
        return workers.find((el: WorkerInfo) => el.worker_id == worker_id);
    };
    /**
     * Получить список сотрудников на линии текущей смены
     * @param {number} line_id идентификатор линии
     * @returns {WorkerInfo[] | undefined} найденные сотрудник для данной линии 
     */
    function getByLine(line_id: number | null): WorkerInfo[] | undefined {
        return workers.filter((el: WorkerInfo) => el.current_line_id == line_id);
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
        _remove,
        _changeWorker,
        _addWorker,
        splice,
        serialize,
        add,
        getById,
        getByLine,
        toSelectOptions
    };
});
