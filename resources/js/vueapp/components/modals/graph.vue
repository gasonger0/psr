<script setup lang="ts">
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { ProductSlot } from '@/store/productsSlots';
import { useWorkersStore, WorkerInfo } from '@/store/workers';
import { useWorkerSlotsStore, WorkerSlot } from '@/store/workerSlots';
import { Button, Modal, Switch, Table, TimePicker } from 'ant-design-vue';
import { ColumnType } from 'ant-design-vue/es/table';
import { DataIndex } from 'ant-design-vue/es/vc-table/interface';
import { computed, onMounted, onUpdated, Ref, ref, watch } from 'vue';

const modal = useModalsStore();
const slots = useWorkerSlotsStore();
const workers = useWorkersStore();
const lines = useLinesStore();

const columns: Array<ColumnType> = [{
    title: 'Сотрудник',
    dataIndex: 'title',
    width: 200,
    fixed: 'left'
} as ColumnType, {
    title: 'Время обеда',
    dataIndex: 'break',
    width: 200,
    fixed: 'left'
} as ColumnType];

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
    if (columns.length == 2) {
        columns.push(
            ...lines.lines.filter((el: LineInfo) => el.has_plans == true).map((i: LineInfo) => {
                let f = i as any;
                f.dataIndex = i.line_id;
                return f;
            }) as ColumnType[]
        );
    }
    cells.value = workers.workers.map((el: WorkerInfo) => {
        slots.getByWorker(el.worker_id).forEach((sl: WorkerSlot) => {
            el[String(sl.line_id)] = sl;
        });
        return el;
    });
}

onUpdated(() => {
    processCells();
});

watch(
    () => slots.slots,
    (ev) => {
        processCells();
    },
    { deep: true }
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
                        v-if="column.dataIndex != 'title' && (record[(column as LineInfo).line_id] || column.dataIndex == 'break')">
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