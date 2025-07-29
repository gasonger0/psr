<script setup lang="ts">
import { Card, Tooltip, Popconfirm, Popover, Select } from 'ant-design-vue';
import { useWorkersStore, WorkerInfo } from '@stores/workers';
import { computed, ref } from 'vue';
import { CoffeeOutlined, UserSwitchOutlined, UserDeleteOutlined } from '@ant-design/icons-vue';
import { notify, SelectOption } from '@/functions';
import { DefaultOptionType, LabelInValueType, RawValueType } from 'ant-design-vue/es/vc-select/Select';
import { useWorkerSlotsStore } from '@stores/workerSlots';

const props = defineProps({
    cardData: {
        type: Object as () => WorkerInfo,
        required: true
    }
});
const store = useWorkersStore();
const workerSelect = store.toSelectOptions();
const slotsStore = useWorkerSlotsStore();
let replacer = ref();

const calcBreak = computed(() : string => {
    return store.calcBreak(props.cardData).value;
});

const deleteWorker = (del: boolean) =>  {
    slotsStore._delete(props.cardData, del);
};
const replaceWorker = async (v: RawValueType | LabelInValueType, ev: DefaultOptionType) => {
    if (ev.key == props.cardData.worker_id) {
        notify('warning', 'Этот один и тот же сотрудник');
        props.cardData.popover = false;
        replacer.value = null;
        return;
    }
    let newWorker = store.getByID(ev.key)

    if (newWorker!.current_line_id) {
        notify('warning', 'Этот сотрудник уже занят на другой линии');
        props.cardData.popover = false;
        replacer.value = null;
        return;
    }
    let res = await slotsStore._replace(props.cardData.worker_id!, ev.key);
    if (res) {
        replacer.value = null;
    }
}

const getBreak = computed(() => {
    return props.cardData.break?.started_at.format('HH:mm') 
        + ' - ' +
        props.cardData.break?.ended_at.format('HH:mm');
})

</script>
<template>
    <Card 
        :title="props.cardData.title" 
        draggable="true" 
        class="draggable-card" 
        :key="props.cardData.worker_id" 
        :class="calcBreak"
        :data-id="props.cardData.worker_id">
        <template #extra>
            <span class="company-title">
                {{ props.cardData.company.title }}
            </span>
        </template>
        <section class="worker">
            <CoffeeOutlined v-show="props.cardData.on_break" />
            <span v-if="props.cardData.break">
                Обед: {{ getBreak }}
            </span>
            <div class="tools" v-show="props.cardData.current_line_id">
                <Tooltip title="Убрать со смены">
                    <Popconfirm 
                        title="Укажите причину" 
                        ok-text="Смена работника закончена досрочно"
                        cancel-text="Работник не вышел на смену" 
                        @confirm="deleteWorker(false)"
                        @cancel="deleteWorker(true)">
                        <UserDeleteOutlined class="worker-icon red" />
                    </Popconfirm>
                </Tooltip>
                <Popover v-model:open="props.cardData.popover" trigger="click" placement="right">
                    <template #content>
                        <Select style="width:20vw;" v-model:value="replacer"
                            :options="workerSelect"
                            :showSearch="true" 
                            @select="replaceWorker" />
                    </template>
                    <Tooltip title="Заменить">
                        <UserSwitchOutlined class="worker-icon yellow" />
                    </Tooltip>
                </Popover>
            </div>
        </section>
    </Card>
</template>