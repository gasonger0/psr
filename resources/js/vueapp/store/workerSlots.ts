import dayjs, { Dayjs } from 'dayjs';
import { defineStore } from 'pinia';
import { reactive, Ref } from 'vue';
import { getRequest } from '../functions';

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
    const slots: WorkerSlot[] = reactive([]);

    async function _load(): Promise<void> {
        slots.values = await getRequest('/api/workers_slots/get');
    }

    return { slots, _load };
});