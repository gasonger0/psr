<script setup lang="ts">
import { DeleteOutlined } from '@ant-design/icons-vue';
import { Modal, Table, TabPane, Tabs, Button, Input, Select, TableSummary, TableSummaryRow, TableSummaryCell, Pagination } from 'ant-design-vue';
import { onBeforeMount, Reactive, reactive, Ref, ref, watch } from 'vue';
import { useWorkersStore, WorkerInfo } from '../../store/workers';
import { responsibleDictColumns, positions, workerDictColumns } from '../../store/dicts';
import { ResponsibleInfo, useResponsiblesStore } from '../../store/responsibles';

defineProps({
    open: {
        type: Boolean,
        required: true
    }
});
defineEmits(['close-modal']);

const workers = useWorkersStore();
const responsibles = useResponsiblesStore();

/**
 * Оригинальные значения
 */
const original: Ref<Object> = ref({
    1: {},
    2: {}
});

/**
 * Должности ответственных для SelectOptions
 */
const poss: Object[] = [];

for (let i in positions) {
    poss.push({
        value: i,
        label: positions[i]
    });
}

/**
 * Активная вкладка
 */
const activeTab: Ref = ref('1');

// Табы справочника сотрудников
const workerDictTabs = {
    1: "Работники",
    2: "Ответственные"
};

/**
 * Добавление на фронте 
 */
const addNewFront = (k: number): void => {
    switch (k) {
        case 1:
            workers.add();
            break;
        case 2:
            responsibles.add();
            break;
    }
    return;
}
/**
 * Сохрнанение данных - создание/редактирование
 */
const save = () : void => {

}
/**
 * Удалить сотрудника
 */
const del = (rec: WorkerInfo | ResponsibleInfo) : void => {
    if ("responsible_id" in rec) {
        responsibles._delete(rec);
    } else {
        workers._delete(rec);
    }
}
/**
 * Отмена редактирования/создания
 */
const cancel = () => {}
/**
 * Начало редактирования
 */
const edit = (rec: WorkerInfo | ResponsibleInfo): void => {
    if ("responsible_id" in rec) {
        original[2][rec.responsible_id] = rec;
    } else if ("worker_id" in rec){
        original[1][rec.worker_id] = rec;
    }
    rec.isEdited!.value = true;
}

onBeforeMount(async () => {
    // TODO Это будет происходить в головном комопоненте
    await responsibles._load();
    await workers._load();
});


const exit = (): void => {
    return;
}

</script>
<template>
    <Modal v-model:open="$props.open" @close="$emit('close-modal')" :closable="false" class="modal workers">
        <div class="workers">
            <Tabs v-model:activeKey="activeTab">
                <TabPane v-for="(v, k) in workerDictTabs" :key="k" :tab="v">
                    <Table 
                        :dataSource="k == 1 ? workers.workers : responsibles.responsibles" 
                        :columns="k == 1 ? workerDictColumns : responsibleDictColumns"
                        :pagination="false">
                        <template #bodyCell="{ column, record, text }">
                            <template v-if="column.dataIndex == 'position'">
                                <Select 
                                    v-model:value="record['position']" 
                                    style="width: 100%;"
                                    :options="poss">
                                </Select>
                            </template>
                            <template v-else-if="column.dataIndex == 'delete'">
                                <DeleteOutlined @click="del(k == 1 ? record as WorkerInfo : record as ResponsibleInfo)"/>
                            </template>
                            <template v-else>
                                <Input v-model:value="record[column.dataIndex]" />
                            </template>
                        </template>
                        <template #summary>
                            <TableSummary>
                                <TableSummaryRow>
                                    <TableSummaryCell :col-span="4" style="padding:0;">
                                        <Button type="primary" @click="addNewFront(k)"
                                            style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                                    </TableSummaryCell>
                                </TableSummaryRow>
                            </TableSummary>
                        </template>
                    </Table>
                </TabPane>
            </Tabs>
        </div>
        <template #footer>
            <Button type="default" @click="exit()">Закрыть</Button>
        </template>
    </Modal>
</template>
