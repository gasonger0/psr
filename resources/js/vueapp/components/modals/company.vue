<script setup lang="ts">
import { DeleteOutlined, SaveOutlined, UndoOutlined } from '@ant-design/icons-vue';
import { Modal, Table, TabPane, Tabs, Button, Input, Select, TableSummary, TableSummaryRow, TableSummaryCell, Pagination, Tooltip, SelectOption, InputSearch } from 'ant-design-vue';
import { onBeforeMount, Ref, ref } from 'vue';
import { useWorkersStore, WorkerInfo } from '@stores/workers';
import { responsibleDictColumns, positions, workerDictColumns, companiesDictColumns } from '@stores/dicts';
import { ResponsibleInfo, useResponsiblesStore } from '@stores/responsibles';
import { useModalsStore } from '@stores/modal';
import { useCompaniesStore } from '@/store/companies';

const workers = useWorkersStore();
const responsibles = useResponsiblesStore();
const modal = useModalsStore();
const companies = useCompaniesStore();
/**
 * Оригинальные значения
 */
const original = ref({
    1: {},
    2: {},
    3: {}
});

/**
 * Должности ответственных для SelectOptions
 */
const poss: Object[] = [];

for (let i in positions) {
    poss.push({
        value: String(i),
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
const addNewFront = (): void => {
    switch (activeTab.value) {
        case 1:
            workers.add();
            break;
        case 2:
            responsibles.add();
            break;
        case 3:
            companies.add();
    }
    return;
}
/**
 * Сохрнанение данных - создание/редактирование
 */
const save = (rec: Record<string, any>): void => {
    if ("company_id" in rec) {
        if (!rec.worker_id) {
            workers._create(rec as WorkerInfo);
        } else {
            workers._update(rec as WorkerInfo);
        }
    } else {
        if (!rec.responsible_id) {
            responsibles._create(rec as ResponsibleInfo);
        } else {
            responsibles._update(rec as ResponsibleInfo);
        }
    }
    rec.isEditing = false;
}
/**
 * Удалить сотрудника
 */
const del = (rec: Record<string, any>): void => {
    if ("worker_id" in rec) {
        workers._delete(rec as WorkerInfo);
    } else {
        responsibles._delete(rec as ResponsibleInfo);
    }
}
/**
 * Отмена редактирования/создания
 */
const cancel = (rec: Record<string, any>) => {
    if ("worker_id" in rec) {
        Object.assign(rec, original.value[1][rec.worker_id]);
        delete original.value[1][rec.worker_id];
    } else if ("responsible_id" in rec) {
        Object.assign(rec, original.value[2][rec.responsible_id]);
        delete original.value[2][rec.responsible_id];
    } else {
        Object.assign(rec, original.value[3][rec.company_id]);
        delete original.value[3][rec.company_id]
    }
    rec.isEditing = false;
    return;
}
/**
 * Начало редактирования
 */
const edit = (rec: Record<string, any>): void => {
    if ("worker_id" in rec) {
        if (original.value[1][rec.worker_id]) {
            return;
        }
        original.value[1][rec.worker_id] = Object.assign({}, rec);
    } else if ("responsible_id" in rec) {
        if (original.value[2][rec.responsible_id]) {
            return;
        }
        original.value[2][rec.responsible_id] = Object.assign({}, rec);
    } else {
        if (original.value[3][rec.company_id]) {
            return;
        }
        original.value[3][rec.company_id] = Object.assign({}, rec);
    }
    rec.isEditing = true;
    return;
}


const exit = (): void => {
    const modal = useModalsStore();
    modal.close('workers');
    return;
}

</script>
<template>
    <Modal v-model:open="modal.visibility['workers']" @close="exit" :closable="false" class="modal workers"
        style="width:60%;">
        <div class="workers">
            <Tabs v-model:activeKey="activeTab">
                <TabPane v-for="(v, k) in workerDictTabs" :key="k" :tab="v">
                    <div style="display:flex;flex-direction: column; gap:10px; overflow:hidden">
                        <!-- <InputSearch placeholder="Искать по ФИО" @search="search" /> -->
                        <!-- TODO: можно впилить поиск, чтобы не листать -->
                        <div style="overflow:auto;position: relative;">
                            <Table :data-source="k == 1 ? workers.workers : responsibles.responsibles"
                                :columns="k == 1 ? workerDictColumns : responsibleDictColumns" :pagination="false">
                                <template #bodyCell="{ column, record, text }">
                                    <template v-if="column.dataIndex == 'position'">
                                        <Select v-model:value="record.position" style="width: 100%;" :options="poss"
                                            @dropdownVisibleChange="edit(record)">
                                        </Select>
                                    </template>
                                    <template v-else-if="column.dataIndex == 'company_id'">
                                        <Select v-model:value="record[column.dataIndex as string]" style="width: 100%"
                                            @dropdownVisibleChange="edit(record)">
                                            <SelectOption v-for="i in companies.companies" :value="i.company_id">
                                                {{ i.title }}
                                            </SelectOption>
                                        </Select>
                                    </template>
                                    <template v-else-if="column.dataIndex == 'actions'">
                                        <section class="actions">
                                            <Tooltip title="Удалить">
                                                <DeleteOutlined @click="del(record)" class="action-icon delete" />
                                            </Tooltip>
                                            <Tooltip title="Сохранить">
                                                <SaveOutlined @click="save(record)" class="action-icon save"
                                                    v-if="record.isEditing" />
                                            </Tooltip>
                                            <Tooltip title="Отмена">
                                                <UndoOutlined @click="cancel(record)" class="action-icon undo"
                                                    v-if="record.isEditing" />
                                            </Tooltip>
                                        </section>
                                    </template>
                                    <template v-else>
                                        <Input v-model:value="record[String(column.dataIndex)]" @focus="edit(record)" />
                                    </template>
                                </template>
                                <template #summary>
                                    <TableSummary>
                                        <TableSummaryRow>
                                            <TableSummaryCell :col-span="4" style="padding:0;">
                                                <Button type="primary" @click="addNewFront"
                                                    style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                                            </TableSummaryCell>
                                        </TableSummaryRow>
                                    </TableSummary>
                                </template>
                            </Table>
                        </div>
                    </div>
                </TabPane>
                <!-- Список компаний -->
                <TabPane key="3" tab="Компании">
                    <Table :data-source="companies.companies" :columns="companiesDictColumns" :pagination="false">
                        <template #bodyCell="{ column, record, text }">
                            <template v-if="column.dataIndex == 'actions'">
                                <section class="actions">
                                    <Tooltip title="Удалить">
                                        <DeleteOutlined @click="del(record)" class="action-icon delete" />
                                    </Tooltip>
                                    <Tooltip title="Сохранить">
                                        <SaveOutlined @click="save(record)" class="action-icon save"
                                            v-if="record.isEditing" />
                                    </Tooltip>
                                    <Tooltip title="Отмена">
                                        <UndoOutlined @click="cancel(record)" class="action-icon undo"
                                            v-if="record.isEditing" />
                                    </Tooltip>
                                </section>
                            </template>
                            <template v-else>
                                <Input v-model:value="record[String(column.dataIndex)]" @focus="edit(record)" />
                            </template>
                        </template>
                        <template #summary>
                            <TableSummary>
                                <TableSummaryRow>
                                    <TableSummaryCell :col-span="4" style="padding:0;">
                                        <Button type="primary" @click="addNewFront"
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
