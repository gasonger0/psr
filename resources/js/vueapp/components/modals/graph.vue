<script setup lang="ts">
import { Slot } from '@/store/dicts';
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { usePlansStore } from '@/store/productsPlans';
import { ProductSlot } from '@/store/productsSlots';
import { useWorkersStore, WorkerInfo } from '@/store/workers';
import { useWorkerSlotsStore, WorkerSlot } from '@/store/workerSlots';
import { PlusCircleOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { Button, Modal, Switch, Table, TimeRangePicker } from 'ant-design-vue';
import { ColumnType } from 'ant-design-vue/es/table';
import { DataIndex } from 'ant-design-vue/es/vc-table/interface';
import { computed, onMounted, onUpdated, Ref, ref, watch } from 'vue';
import TimePicker from '../common/timePicker.vue';

const modal = useModalsStore();
const slots = useWorkerSlotsStore();
const plans = usePlansStore();
const workers = useWorkersStore();
const lines = useLinesStore();

const columns: Ref<Array<ColumnType & { work_time?: Slot }>> = ref([]);

const cells: Ref<any[]> = ref([]);

const scroll = {
    x: 'max-content'
}

const getWorkTime = (line: ColumnType<any>) => {
    let data = line as LineInfo;
    return `${data.work_time.started_at.format('HH:mm')} - ${data.work_time.ended_at.format('HH:mm')}`;
}

const formatSlotTime = (rec: Record<string, any>, col: ColumnType) => {
    if (col.dataIndex == "break") {
        return `${rec.break.started_at.format('HH:mm')} - ${rec.break.ended_at.format('HH:mm')}`;
    } else {
        let slot = rec[col.dataIndex as string];
        if (!slot) {
            return "";
        }
        return `${slot.started_at.format('HH:mm')} - ${slot.ended_at.format('HH:mm')}`;
    }
}

const changeTime = (slot: WorkerSlot|WorkerInfo, isBreak: boolean = false) => {
    if (isBreak) {
        // Обновляем обед сотрудника
        workers._update(slot as WorkerInfo);
    } else {
        // обновляем слот
        slots._update(slot as WorkerSlot);
    }
};
const addSlot = async (lineCol: ColumnType, rec: Record<string, any>) => {
    let line = useLinesStore().getByID(Number(lineCol.dataIndex));
    let newSlot = await slots.add(line, rec.worker_id);
    // rec[newSlot.line_id].push(newSlot);
};
const deleteSlot = async (slot: WorkerSlot) => {
    let index = cells.value.findIndex(el => el.worker_id == slot.worker_id);
    await slots._remove(slot);
    let slotIndex = cells.value[index][slot.line_id].indexOf(slot);
    delete cells.value[index][slot.line_id][slotIndex];
    cells.value[index][slot.line_id] = cells.value[index][slot.line_id].filter(i => i != null)
}

const exit = () => {
    modal.close('graph');
}

const processCells = () => {
    columnsReset();
    lines.lines.filter(el => el.has_plans == true).forEach(i => {
        columns.value.push({
            title: i.title,
            dataIndex: i.line_id,
            width: 200,
            work_time: i.work_time
        });
    });

    cells.value = workers.workers.map((el: WorkerInfo) => {
        slots.getByWorker(el.worker_id).forEach((sl: WorkerSlot) => {
            if (!el[sl.line_id]) {
                el[sl.line_id] = [];
            }
            if (!el[sl.line_id].find((i) => i.slot_id == sl.slot_id)) {
                el[String(sl.line_id)].push(sl);
            }

            if (columns.value.find((el) => el.dataIndex == sl.line_id) === undefined) {
                let line = lines.getByID(sl.line_id);
                columns.value.push({
                    title: line.title,
                    dataIndex: line.line_id,
                    width: 200,
                    work_time: line.work_time
                });
            }
        });
        return el;
    });
}

const columnsReset = () => {
    columns.value = [{
        title: 'Сотрудник',
        dataIndex: 'title',
        width: 200,
        fixed: 'left'
    }, {
        title: 'Время обеда',
        dataIndex: 'break',
        width: 200,
        fixed: 'left'
    }];
}

onUpdated(() => {
    processCells();
});

watch(
    () => slots.slots,
    (ev) => {
        processCells();
    },
    {
        deep: true,
        immediate: true
    }
);

watch(
    () => plans.plans,
    (ev) => {
        processCells();
    },
    {
        deep: true,
        immediate: true
    }
);
</script>
<template>
    <Modal v-model:open="modal.visibility['graph']" cancelText="Закрыть" title="Редактировать график" @ok="exit"
        @cancel="exit" wrap-class-name="modal graph" class="modal graph">
        <div class="table-container">
            <Table :data-source="cells" :columns="columns" small :scroll="scroll" :pagination="{ pageSize: 8 }">
                <template #headerCell="{ title, column }">
                    <div class="centered-cell">
                        <span style="width:160px">{{ title }}</span>
                        <span style="color:gray" v-if="column.dataIndex != 'title' && column.dataIndex != 'break'">
                            {{ getWorkTime(column) }}
                        </span>
                    </div>
                </template>
                <template #bodyCell="{ column, record }">
                    <template
                        v-if="
                            column.dataIndex != 'title' && 
                            (record[column.dataIndex as string] && record[column.dataIndex as string].length > 0
                             || column.dataIndex == 'break')
                            ">
                        <div class="pickers" v-if="column.dataIndex == 'break'">
                            <TimePicker :model="record[column.dataIndex as string]"
                                @change="changeTime(record[column.dataIndex as string] as WorkerInfo, true)" />
                        </div>
                        <div class="pickers" v-for="(slot, k) in record[column.dataIndex as string]" v-else>
                            <TimePicker 
                                v-if="slot"
                                :model="slot" :last="record[column.dataIndex as string].length == k+1" 
                                :dels="true"
                                @change="changeTime(slot)" 
                                @add="addSlot(column, record)" 
                                @del="deleteSlot(slot)"/>
                        </div>
                    </template>
                    <template v-else-if="!record[column.dataIndex as string] || record[column.dataIndex as string].length == 0">
                        <Button class="footer-button" type="dashed" @click="addSlot(column, record)">
                            +
                        </Button>
                    </template>
                    <template v-else>
                        <span class="overflow-span">{{ record[column.dataIndex as string] }}</span>
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
</template>