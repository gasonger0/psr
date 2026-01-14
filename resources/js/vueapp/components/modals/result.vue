<script setup lang="ts">
import { useModalsStore } from '@/store/modal';
import { useWorkersStore, WorkerInfo } from '@/store/workers';
import { useWorkerSlotsStore, WorkerSlot } from '@/store/workerSlots';
import { Modal, Input, Table, Checkbox } from 'ant-design-vue';
import { onMounted, onUpdated, ref, Ref, watch } from 'vue';

const modal = useModalsStore(),
    slotsStore = useWorkerSlotsStore(),
    workersStore = useWorkersStore();

const workers: Ref<{check: boolean, worker_id: number, ktu: number}[]> = ref();

const getWorkers = () => {
    const active = slotsStore.slots.map(el => el.worker_id);
    workers.value = workersStore.workers.filter(i => active.includes(i.worker_id)).map(el => {
        return {
            check: true,
            worker_id: el.worker_id,
            ktu: 1
        };
    });

    console.log(workers.value, active);
}

const close = async (get: boolean) => {
    if (get) {
        await workersStore._result(workers.value);
    }
    modal.close('result');
    workers.value = [];
}

watch(
    () => slotsStore.slots,
    () => getWorkers(),
    {deep: true}
);

onMounted(() => getWorkers())

</script>
<template>
    <Modal v-model:open="modal.visibility['result']" cancelText="Закрыть" okText="Сформировать отчёт"
        style="min-width:20vw; min-height: 30vh;" @ok="close(true)" @cancel="close(false)" title="Сформировать отчёт">
        <div class="table-container">
            <Table :dataSource="workers" :columns="[{
                dataIndex: 'check',
                title: ''
            }, {
                dataIndex: 'title',
                title: 'Работник'
            }, {
                dataIndex: 'ktu',
                title: 'КТУ'
            }]">
                <template #bodyCell="{ record, column, text }">
                    <template v-if="column.dataIndex == 'ktu'">
                        <Input v-model:value="record.ktu" />
                    </template>
                    <template v-else-if="column.dataIndex == 'check'">
                        <Checkbox v-model:checked="record[column.dataIndex]" />
                    </template>
                    <template v-else>
                        {{ workersStore.getByID(record.worker_id).title }}
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
</template>