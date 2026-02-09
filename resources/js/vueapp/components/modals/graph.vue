<script setup lang="ts">
import { Slot } from '@/store/dicts';
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { usePlansStore } from '@/store/productsPlans';
import { ProductSlot } from '@/store/productsSlots';
import { useWorkersStore, WorkerInfo } from '@/store/workers';
import { useWorkerSlotsStore, WorkerSlot } from '@/store/workerSlots';
import { Button, Modal, Switch, Table, TimePicker } from 'ant-design-vue';
import { ColumnType } from 'ant-design-vue/es/table';
import { DataIndex } from 'ant-design-vue/es/vc-table/interface';
import { computed, onMounted, onUpdated, Ref, ref, watch } from 'vue';

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

const changeTime = (index: DataIndex, record: Record<string, any>) => {
    if (index == "break") {
        // Обновляем обед сотрудника
        workers._update(record as WorkerInfo);
    } else {
        // обновляем слот
        useWorkerSlotsStore()._update(record[index as string]);
    }
};
const addSlot = async (line: ColumnType, rec: Record<string, any>) => {
    let newSlot = await slots._add(line as LineInfo, rec.worker_id);
    rec[newSlot.line_id] = newSlot;
};

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
            el[String(sl.line_id)] = sl;
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

    console.log(columns, lines.lines.filter(i => i.has_plans));
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
                        v-if="column.dataIndex != 'title' && (record[column.dataIndex as string] || column.dataIndex == 'break')">
                        <div class="pickers">
                            <TimePicker v-model:value="record[column.dataIndex as string].started_at" format="HH:mm"
                                :showTime="true" :allowClear="true" type="time" :showDate="false" size="small"
                                class="timepicker" @change="changeTime(column.dataIndex, record)" />
                            <span> - </span>
                            <TimePicker v-model:value="record[column.dataIndex as string].ended_at" format="HH:mm"
                                :showTime="true" :allowClear="true" type="time" :showDate="false" size="small"
                                class="timepicker" @change="changeTime(column.dataIndex, record)" />
                        </div>
                    </template>
                    <template v-else-if="!record[column.dataIndex as string]">
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