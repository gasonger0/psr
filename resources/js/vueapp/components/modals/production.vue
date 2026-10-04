<script setup lang="ts">
import { ref, watch } from 'vue';
import { useModalsStore } from '@/store/modal';
import { HardwareInfo, RegistryLine, ReturnTypeInfo, useProductionStore } from '@/store/production';
import { useLinesStore } from '@/store/lines';
import {
    Modal, Tabs, TabPane, Table, Button, Input, InputNumber, Select, Checkbox, Popconfirm
} from 'ant-design-vue';
import { DeleteOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons-vue';

const modal = useModalsStore();
const production = useProductionStore();
const linesStore = useLinesStore();

watch(() => modal.visibility['production'], (open) => {
    if (open && production.registryLines.length === 0) {
        production._loadRegistry();
    }
});

/* ===== Линии ===== */
const selectedLineIds = ref<number[]>([]);
const bulkModalOpen = ref(false);
const bulk = ref<{
    type_id?: number | null;
    return_type?: string | number | null;
    use_dating?: number | null;
    perfomance?: string;
    prep_time?: number | null;
    after_time?: number | null;
    workers_count?: number | null;
}>({});

const onSelectLines = (keys: (string | number)[]) => {
    selectedLineIds.value = keys as number[];
};

const lineColumns = [
    { title: 'Наименование', dataIndex: 'title', key: 'title', width: 200 },
    { title: 'Производительность', dataIndex: 'perfomance', key: 'perfomance', width: 180 },
    { title: 'Тип', dataIndex: 'type_id', key: 'type_id', width: 140 },
    { title: 'Подг. время', dataIndex: 'prep_time', key: 'prep_time', width: 95 },
    { title: 'Закл. время', dataIndex: 'after_time', key: 'after_time', width: 95 },
    { title: 'Рабочие', dataIndex: 'workers_count', key: 'workers_count', width: 90 },
    { title: 'Возвратные массы', dataIndex: 'return_type', key: 'return_type', width: 190 },
    { title: 'Датирование', dataIndex: 'use_dating', key: 'use_dating', width: 110 },
    { title: 'Цвет', dataIndex: 'color', key: 'color', width: 60 },
    { title: '', key: 'actions', width: 90 }
];

const saveLineRow = async (row: RegistryLine) => {
    await production._bulkUpdateLines([row.line_id], {
        title: row.title,
        perfomance: row.perfomance ?? null,
        type_id: row.type_id,
        prep_time: row.prep_time ?? null,
        after_time: row.after_time ?? null,
        workers_count: row.workers_count ?? null,
        return_type: row.return_type ?? null,
        use_dating: row.use_dating ?? false,
        color: row.color ?? null
    });
    await production._loadRegistry();
};

const addLine = async () => {
    const line = linesStore.add();
    await linesStore._create(line);
    await linesStore._load();
    await production._loadRegistry();
};

const deleteLine = async (row: RegistryLine) => {
    await production._deleteLine(row.line_id);
    await linesStore._load();
    await production._loadRegistry();
};

const applyBulk = async () => {
    const fields: Record<string, any> = {};
    if (bulk.value.type_id !== undefined && bulk.value.type_id !== null) {
        fields.type_id = bulk.value.type_id;
    }
    if (bulk.value.return_type === '__clear__') {
        fields.return_type = null;
    } else if (bulk.value.return_type !== undefined && bulk.value.return_type !== null) {
        fields.return_type = bulk.value.return_type;
    }
    if (bulk.value.use_dating === 1) {
        fields.use_dating = true;
    } else if (bulk.value.use_dating === 0) {
        fields.use_dating = false;
    }
    if (bulk.value.perfomance) {
        fields.perfomance = bulk.value.perfomance;
    }
    if (bulk.value.prep_time !== undefined && bulk.value.prep_time !== null) {
        fields.prep_time = bulk.value.prep_time;
    }
    if (bulk.value.after_time !== undefined && bulk.value.after_time !== null) {
        fields.after_time = bulk.value.after_time;
    }
    if (bulk.value.workers_count !== undefined && bulk.value.workers_count !== null) {
        fields.workers_count = bulk.value.workers_count;
    }
    await production._bulkUpdateLines(selectedLineIds.value, fields);
    bulkModalOpen.value = false;
    bulk.value = {};
    await production._loadRegistry();
};

/* ===== Оборудование ===== */
const hardwareColumns = [
    { title: 'Название', dataIndex: 'title', key: 'title' },
    { title: 'Полное название', dataIndex: 'full_title', key: 'full_title' },
    { title: 'Тип', dataIndex: 'type', key: 'type', width: 160 },
    { title: '', key: 'actions', width: 90 }
];

const saveHardware = async (h: HardwareInfo) => {
    if (h.hardware_id) {
        await production._updateHardware(h);
    } else {
        await production._createHardware(h);
    }
    await production._load();
};

const deleteHardware = async (h: HardwareInfo) => {
    await production._deleteHardware(h.hardware_id);
    await production._load();
};

const addHardware = () => {
    production.hardwares.push({ hardware_id: 0, title: '', full_title: '', type: 1 });
};

/* ===== Возвратные массы ===== */
const returnTypeColumns = [
    { title: 'Название', dataIndex: 'title', key: 'title' },
    { title: 'Формула', dataIndex: 'formula_type', key: 'formula_type', width: 160 },
    { title: 'Фикс. значение', dataIndex: 'fixed_value', key: 'fixed_value', width: 120 },
    { title: 'Зефир', dataIndex: 'coef_z', key: 'coef_z', width: 100 },
    { title: 'Суфле', dataIndex: 'coef_s', key: 'coef_s', width: 100 },
    { title: 'Конфеты', dataIndex: 'coef_k', key: 'coef_k', width: 100 },
    { title: '', key: 'actions', width: 90 }
];

const saveReturnType = async (r: ReturnTypeInfo) => {
    if (r.return_type_id) {
        await production._updateReturnType(r);
    } else {
        await production._createReturnType(r);
    }
    await production._load();
};

const deleteReturnType = async (r: ReturnTypeInfo) => {
    await production._deleteReturnType(r.return_type_id);
    await production._load();
};

const addReturnType = () => {
    production.returnTypes.push({
        return_type_id: 0, title: '', formula_type: 'coef',
        fixed_value: null, coef_z: null, coef_s: null, coef_k: null
    });
};

const saveSettings = async () => {
    await production._saveSettings();
};
</script>

<template>
    <Modal v-model:open="modal.visibility['production']" title="Производство" :closable="true"
        wrap-class-name="modal production" class="modal production" :footer="null">
        <Tabs tab-position="left">
            <TabPane key="lines" tab="Линии">
                <div class="pane-scroll">
                    <div class="pane-toolbar">
                        <Button type="primary" @click="addLine">
                            <PlusOutlined /> Добавить линию
                        </Button>
                        <Button :disabled="selectedLineIds.length == 0" @click="bulkModalOpen = true">
                            Изменить выделенные
                        </Button>
                    </div>
                    <div class="pane-table">
                        <Table :columns="lineColumns" :data-source="production.registryLines"
                            :row-key="(r: RegistryLine) => r.line_id"
                            :row-selection="{ selectedRowKeys: selectedLineIds, onChange: onSelectLines }"
                            :pagination="false" size="small"
                            :loading="production.registryLoading">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex == 'title'">
                                    <Input v-model:value="record.title" />
                                </template>
                                <template v-else-if="column.dataIndex == 'perfomance'">
                                    <Input v-model:value="record.perfomance" />
                                </template>
                                <template v-else-if="column.dataIndex == 'type_id'">
                                    <Select v-model:value="record.type_id" style="width:100%"
                                        :options="[{ value: '1', label: 'Варка' }, { value: '2', label: 'Упаковка' }, { value: '3', label: 'Сборка ящиков' }]" />
                                </template>
                                <template v-else-if="['prep_time', 'after_time', 'workers_count'].includes(column.dataIndex)">
                                    <InputNumber v-model:value="record[column.dataIndex]" :min="0" style="width:100%" />
                                </template>
                                <template v-else-if="column.dataIndex == 'return_type'">
                                    <Select v-model:value="record.return_type" style="width:100%" :allowClear="true"
                                        placeholder="Не участвует" :options="production.returnTypeOptions" />
                                </template>
                                <template v-else-if="column.dataIndex == 'use_dating'">
                                    <Checkbox v-model:checked="record.use_dating" />
                                </template>
                                <template v-else-if="column.dataIndex == 'color'">
                                    <input type="color" v-model="record.color" />
                                </template>
                                <template v-else-if="column.dataIndex == 'actions'">
                                    <Button type="primary" size="small" @click="saveLineRow(record)">
                                        <SaveOutlined />
                                    </Button>
                                    <Popconfirm title="Удалить линию?" @confirm="deleteLine(record)">
                                        <Button type="dashed" danger size="small">
                                            <DeleteOutlined />
                                        </Button>
                                    </Popconfirm>
                                </template>
                            </template>
                        </Table>
                    </div>
                </div>
            </TabPane>

            <TabPane key="hardwares" tab="Оборудование">
                <div class="pane-scroll">
                    <div class="pane-toolbar">
                        <Button type="primary" @click="addHardware">
                            <PlusOutlined /> Добавить оборудование
                        </Button>
                    </div>
                    <div class="pane-table">
                        <Table :columns="hardwareColumns" :data-source="production.hardwares"
                            :row-key="(r: HardwareInfo) => r.hardware_id" :pagination="false" size="small">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex == 'title'">
                                    <Input v-model:value="record.title" />
                                </template>
                                <template v-else-if="column.dataIndex == 'full_title'">
                                    <Input v-model:value="record.full_title" />
                                </template>
                                <template v-else-if="column.dataIndex == 'type'">
                                    <Select v-model:value="record.type" style="width:100%"
                                        :options="[{ value: 1, label: 'Варка' }, { value: 2, label: 'Упаковка' }]" />
                                </template>
                                <template v-else-if="column.dataIndex == 'actions'">
                                    <Button type="primary" size="small" @click="saveHardware(record)">
                                        <SaveOutlined />
                                    </Button>
                                    <Popconfirm title="Удалить оборудование?" @confirm="deleteHardware(record)">
                                        <Button type="dashed" danger size="small">
                                            <DeleteOutlined />
                                        </Button>
                                    </Popconfirm>
                                </template>
                            </template>
                        </Table>
                    </div>
                </div>
            </TabPane>

            <TabPane key="return_types" tab="Возвратные массы">
                <div class="pane-scroll">
                    <div class="pane-toolbar">
                        <Button type="primary" @click="addReturnType">
                            <PlusOutlined /> Добавить режим
                        </Button>
                    </div>
                    <div class="pane-table">
                        <Table :columns="returnTypeColumns" :data-source="production.returnTypes"
                            :row-key="(r: ReturnTypeInfo) => r.return_type_id" :pagination="false" size="small">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex == 'title'">
                                    <Input v-model:value="record.title" />
                                </template>
                                <template v-else-if="column.dataIndex == 'formula_type'">
                                    <Select v-model:value="record.formula_type" style="width:100%"
                                        :options="[{ value: 'fixed', label: 'Фикс. значение' }, { value: 'coef', label: 'Коэффициент' }]" />
                                </template>
                                <template v-else-if="column.dataIndex == 'fixed_value'">
                                    <InputNumber v-if="record.formula_type == 'fixed'" v-model:value="record.fixed_value"
                                        :min="0" style="width:100%" />
                                </template>
                                <template v-else-if="['coef_z', 'coef_s', 'coef_k'].includes(column.dataIndex)">
                                    <InputNumber v-if="record.formula_type == 'coef'" v-model:value="record[column.dataIndex]"
                                        :min="0" style="width:100%" />
                                </template>
                                <template v-else-if="column.dataIndex == 'actions'">
                                    <Button type="primary" size="small" @click="saveReturnType(record)">
                                        <SaveOutlined />
                                    </Button>
                                    <Popconfirm title="Удалить режим?" @confirm="deleteReturnType(record)">
                                        <Button type="dashed" danger size="small">
                                            <DeleteOutlined />
                                        </Button>
                                    </Popconfirm>
                                </template>
                            </template>
                        </Table>
                    </div>
                </div>
            </TabPane>

            <TabPane key="settings" tab="Постоянные величины">
                <div style="display:flex; flex-direction:column; gap:12px; max-width:420px;">
                    <label>Интервал варки (мин):
                        <InputNumber v-model:value="production.settings.interval_boil" :min="0" />
                    </label>
                    <label>Интервал упаковки (мин):
                        <InputNumber v-model:value="production.settings.interval_pack" :min="0" />
                    </label>
                    <label>Производительность ЗМ:
                        <InputNumber v-model:value="production.settings.zm_perfomance" :min="0" />
                    </label>
                    <label>Производительность 2×ЗМ:
                        <InputNumber v-model:value="production.settings.zm_perfomance2" :min="0" />
                    </label>
                    <Button type="primary" style="width:160px;" @click="saveSettings">Сохранить</Button>
                </div>
            </TabPane>
        </Tabs>

        <Modal v-model:open="bulkModalOpen" title="Изменить выделенные линии" @ok="applyBulk">
            <div style="display:flex; flex-direction:column; gap:8px;">
                <Select v-model:value="bulk.type_id" placeholder="Тип — не менять" :allowClear="true"
                    :options="[{ value: '1', label: 'Варка' }, { value: '2', label: 'Упаковка' }, { value: '3', label: 'Сборка ящиков' }]" />
                <Select v-model:value="bulk.return_type" placeholder="Возвратные массы — не менять"
                    :options="[...production.returnTypeOptions, { value: '__clear__', label: 'Очистить (не участвует)' }]" />
                <Select v-model:value="bulk.use_dating" placeholder="Датирование — не менять"
                    :options="[{ value: 1, label: 'Да' }, { value: 0, label: 'Нет' }]" />
                <Input v-model:value="bulk.perfomance" placeholder="Производительность" />
                <InputNumber v-model:value="bulk.prep_time" placeholder="Подготовительное время" :min="0" />
                <InputNumber v-model:value="bulk.after_time" placeholder="Заключительное время" :min="0" />
                <InputNumber v-model:value="bulk.workers_count" placeholder="Рабочие" :min="0" />
            </div>
        </Modal>
    </Modal>
</template>
