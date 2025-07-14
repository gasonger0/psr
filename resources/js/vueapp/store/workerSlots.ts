import dayjs, { Dayjs } from 'dayjs';
import { defineStore } from 'pinia';
import { reactive, ref, Ref } from 'vue';
import { getRequest, deleteRequest, notify, putRequest, postRequest } from '../functions';
import { useWorkersStore, WorkerInfo } from './workers';
import { AxiosError, AxiosResponse } from 'axios';
import { useLinesStore } from './lines';

export type WorkerSlot = {
    slot_id?: number,
    worker_id: number,
    line_id: number,
    time_planned: Dayjs
    started_at: Dayjs,
    ended_at: Dayjs,
    date: Dayjs,
    isDay: boolean,
    popover?: Ref
};

export const useWorkerSlotsStore = defineStore('workersSlots', () => {
    const slots: Ref<WorkerSlot[]> = ref([]);
    /**
     * Загружает данные в хранилище из БД
     */
    async function _load(): Promise<void> {
        slots.value = await getRequest('/api/workers_slots/get');
    }
    /**
     * Создаёт новый рабочий слот сотрудника
     * @param fields поля слота
     */
    async function _create(worker: WorkerInfo, line_id: number): Promise<void> {
        const ls = useLinesStore();
        let line = ls.getById(line_id);
        await postRequest('/api/workers_slots/create', {
            worker_id: worker.worker_id,
            line_id: line_id,
            started_at: dayjs(),
            ended_at: line?.work_time.ended_at
        },
            (r: AxiosResponse) => {
                slots.value.push(r.data);
            }
        )
    }
    /**
     * Удалить работника со смены
     * @param rec Запись о слоте сотрудника
     * @param del 
     */
    async function _delete(rec: WorkerInfo, del: Boolean) {
        await deleteRequest('/api/workers_slots/delete', {
            worker_id: rec.worker_id,
            slot_id: rec.current_slot_id,
            delete: del
        }, (response: AxiosResponse) => {
            splice(rec.current_slot_id!);
            rec.current_slot_id = undefined;
            rec.current_line_id = undefined;
            notify('success', `Сотрудник ${rec.title} убран со смены`);
            return;
        });
    };
    /**
     * Замена работника на смене
     * @param old_worker 
     * @param new_worker 
     * @returns 
     */
    async function _replace(old_worker_id: number, new_worker_id: number) {
        const ws = useWorkersStore();
        let old_worker = ws.getById(old_worker_id);
        let new_worker = ws.getById(new_worker_id);
        return await putRequest('/api/workers_slots/replace', {
            old_worker_id: old_worker!.worker_id,
            slot_id: old_worker!.current_slot_id,
            new_worker_id: new_worker!.worker_id
        }, async (r: any) => {
            old_worker!.popover = false;
            splice(old_worker!.current_slot_id!);
            old_worker!.current_line_id = undefined;
            old_worker!.current_slot_id = undefined;
            new_worker!.current_slot_id = r.slot_id;
            new_worker!.current_line_id = r.line_id;
            return true;
        }, (err: AxiosError) => {
            notify('error', err.message);
            return false;
        });
    };
    /**
     * Обновить слот (передвинуть сотрудника на другую линию)
     * @param old_worker_id 
     * @param new_worker_id 
     */
    async function _change(worker: WorkerInfo, line_id: number) {
        return await putRequest('/api/workers_slots/change', {
            old_slot_id: worker.current_slot_id,
            new_line_id: line_id
        },
            (r: AxiosResponse) => {
                worker.current_line_id = r.data.line_id;
                worker.current_slot_id = r.data.slot_id;
            });
    }

    /**
     * Удалить слот из лоакального хранилища
     * @param id ИД слота
     */
    function splice(id: number): void {
        slots.value = slots.value.filter((n: WorkerSlot) => n.slot_id != id);
    };

    return {
        slots,
        _load,
        _create,
        // _update,
        _delete,
        _change,
        _replace,
        // _print 
        // TODO будет в контроллере таблиц мб?
    };
});