import * as dayjs from 'dayjs';
import { defineStore } from 'pinia';
import { reactive, ref, Ref } from 'vue';
import { getRequest, deleteRequest, notify, putRequest, postRequest, getTimeString } from '../functions';
import { useWorkersStore, WorkerInfo } from './workers';
import { AxiosError, AxiosResponse } from 'axios';
import { LineInfo, useLinesStore } from './lines';
import { format } from './dicts';

export type WorkerSlot = {
    slot_id?: number,
    worker_id: number,
    line_id: number,
    // time_planned: dayjs.Dayjs
    started_at: dayjs.Dayjs,
    ended_at: dayjs.Dayjs,
    date: dayjs.Dayjs,
    isDay: boolean,
    popover?: Ref
};

export const useWorkerSlotsStore = defineStore('workersSlots', () => {
    const slots: Ref<WorkerSlot[]> = ref([]);


    /****** API ******/
    /**
     * Загружает данные в хранилище из БД
     */
    async function _load(): Promise<void> {
        slots.value = (await getRequest('/api/workers_slots/get')).map((el: WorkerSlot) => {
            el.started_at = dayjs.default(el.started_at, format);
            el.ended_at = dayjs.default(el.ended_at, format);
            return el;
        });
    }
    /**
     * Создаёт новый рабочий слот сотрудника
     * @param fields поля слота
     */
    async function _create(worker: WorkerInfo, line_id: number): Promise<void> {
        const ls = useLinesStore();
        let line = ls.getByID(line_id);
        await postRequest('/api/workers_slots/create', {
            worker_id: worker.worker_id,
            line_id: line_id,
            started_at: dayjs.default().format(format),
            ended_at: line?.work_time.ended_at.format(format)
        },
            (r: AxiosResponse) => {
                slots.value.push(r.data);
                worker.current_line_id = line_id;
                worker.current_slot_id = r.data.slot_id;
            }
        )
    }
    async function _update(slot: WorkerSlot): Promise<void> {
        await putRequest('/api/workers_slots/update', unserialize(slot));
    }
    /**
     * Удалить работника со смены
     * @param rec Запись о слоте сотрудника
     * @param del 
     */
    async function _delete(rec: WorkerInfo, del: Boolean) {
        await deleteRequest('/api/workers_slots/delete', {
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
        let old_worker = ws.getByID(old_worker_id);
        let new_worker = ws.getByID(new_worker_id);
        return await putRequest('/api/workers_slots/replace', {
            slot_id: old_worker!.current_slot_id!,
            new_worker_id: new_worker!.worker_id
        }, async (r: any) => {
            old_worker!.popover = false;
            splice(old_worker!.current_slot_id!);
            new_worker!.current_slot_id = r.slot_id;
            new_worker!.current_line_id = old_worker!.current_line_id;
            old_worker!.current_line_id = undefined;
            old_worker!.current_slot_id = undefined;
            return true;
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

    /****** LOCAL ******/
    /**
     * Удалить слот из лоакального хранилища
     * @param id ИД слота
     */
    function splice(id: number): void {
        slots.value = slots.value.filter((n: WorkerSlot) => n.slot_id != id);
    };
    function getByWorker(id: number): WorkerSlot[] {
        return slots.value.filter((el: WorkerSlot) => el.worker_id == id);
    }
    async function _add(line: LineInfo, worker_id: number) {
        slots.value.push({
            worker_id: worker_id,
            line_id: line.line_id,
            // time_planned: dayjs.default(),
            started_at: line.work_time.started_at,
            ended_at: line.work_time.ended_at,
            isDay: line.isDay,
            date: line.date
        });

        let slot = slots.value.at(-1);
        await postRequest('/api/workers_slots/create', unserialize(slot),
            (r: AxiosResponse) => {
                slot.slot_id = r.data.slot_id;
            });
        return slot;
    }
    function unserialize(slot: WorkerSlot) {
        let payload = JSON.parse(JSON.stringify(slot));
        payload.started_at = slot.started_at.format(format);
        payload.ended_at = slot.ended_at.format(format);
        return payload;
    }

    function getCurrent() : WorkerSlot[] {
        let ts = getTimeString();
        return slots.value.filter(el => 
            el.started_at <= ts && el.ended_at >= ts
        );
    }

    return {
        slots,
        _add,
        _load,
        _create,
        _update,
        _delete,
        _change,
        _replace,
        getByWorker,
        getCurrent
    };
});