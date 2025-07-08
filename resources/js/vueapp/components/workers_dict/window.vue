<script setup lang="ts">
import { DeleteOutlined } from '@ant-design/icons-vue';
import { Modal, Table, TabPane, Tabs, Button, Input, Select, TableSummary, TableSummaryRow, TableSummaryCell, Pagination } from 'ant-design-vue';
import axios from 'axios';
import { onBeforeMount, Reactive, reactive, Ref, ref, watch } from 'vue';
import { useWorkersStore, WorkerInfo } from '../../store/workers';
import { workerDictTabs, responsibleDictColumns, positions, workerDictColumns } from '../../store/dicts';
import { ResponsibleInfo, ResponsibleStore, useResponsiblesStore } from '../../store/responsibles';

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
 * Должности ответственных для SelectOptions
 */
const poss: Object[] = [];

for (let i in positions) {
    poss.push({
        value: i,
        label: positions[i]
    });
}

let activeTab: Ref = ref(1);
/**
 * Активные страницы в таблицах
 */
let pageRef: Object = {
    1: ref(1),
    2: ref(2)
};
// TODO дополнить
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
const save = () : void => {

}
const del = (rec: WorkerInfo | ResponsibleInfo) : void => {
    if ("responsible_id" in rec) {
        responsibles._delete(rec);
    }
}

onBeforeMount(async () => {
    // TODO Это будет происходить в головном комопоненте
    await responsibles._load();
    await workers._load();
});


const exit = (): void => {
    return;
}
//     axios.post('/api/edit_workers', this.workers);
//     axios.post('/api/edit_responsible', this.responsibles);
//     this.$emit('close-modal');
// } 

</script>
<template>
    <Modal v-model:open="$props.open" @close="$emit('close-modal')" :closable="false" style="width:40%;height:80%;">
        <div style="max-height:74vh;overflow:auto;">
            <Tabs v-model:activeKey="activeTab">
                <TabPane v-for="(v, k) in workerDictTabs" :key="k" :tab="v">
                    <Table 
                        :dataSource="k == 1 ? workers.workers : responsibles.responsibles" 
                        :columns="k == 1 ? workerDictColumns : responsibleDictColumns"
                        :pagination="false">
                        <template #bodyCell="{ column, record, text }">
                            <template v-if="column.dataIndex == 'position'">
                                <Select 
                                    v-model:value="text" 
                                    style="width: 100%;"
                                    :options="poss">
                                </Select>
                            </template>
                            <template v-else-if="column.dataIndex == 'delete'">
                                <DeleteOutlined @click="del(k == 1 ? record as WorkerInfo : record as ResponsibleInfo)" />
                            </template>
                            <template v-else>
                                <Input v-model:value="text" />
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
