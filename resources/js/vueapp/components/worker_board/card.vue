<script setup lang="ts">
import { Card, Tooltip, Popconfirm, Popover } from 'ant-design-vue';
import { useWorkersStore, WorkerInfo } from '../../store/workers.ts';
import { computed, ref } from 'vue';
import { CoffeeOutlined } from '@ant-design/icons-vue';
import { notify, SelectOption } from '../../functions';

const props = defineProps({
    cardData: {
        type: Object as () => WorkerInfo,
        required: true
    }
});
const store = useWorkersStore();
const workerSelect = store.toSelectOptions();
let replacer = ref(null);

const calcBreak = computed(() : string => {
    return store.calcBreak(props.cardData).value;
});

const deleteWorker = (del: boolean) =>  {
    store._remove(props.cardData, del);
};
const changeWorker = async (v: string, ev: SelectOption) => {
    if (ev.key == props.cardData.worker_id) {
        notify('warning', 'Этот один и тот же сотрудник');
        props.cardData.popover = false;
        replacer.value = null;
        return;
    }
    let newWorker = store.getById(ev.key)
    if (!newWorker){
        return;     // TODO придумать как обработать
    }
    if (newWorker?.current_line_id) {
        notify('warning', 'Этот сотрудник уже занят на другой линии');
        props.cardData.popover = false;
        replacer.value = null;
        return;
    }
    let res = await store._changeWorker(props.cardData, newWorker);
    if (res) {
        replacer.value = null;
        // TODO: emit $forceUpdate в родительский компонент???
    }
}

</script>
<template>
    <!-- TODO: 
     1) data-id менять на ref
     2) Не факт, что с просами корректно работаю -->
    <Card 
        :title="props.cardData.title" 
        draggable="true" 
        class="draggable-card" 
        :key="props.cardData.worker_id" 
        :class="calcBreak"
        :data-id="props.cardData.worker_id">
        <template #extra>
            <span class="company-title">
                {{ props.cardData.company }}
            </span>
        </template>
        <section class="worker">
            <CoffeeOutlined v-show="props.cardData.on_break" />
            <span v-if="props.cardData.break">
                Обед: {{ props.cardData.break.started_at }} - {{ props.cardData.break.ended_at }}
            </span>
            <div class="tools">
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
                            @select="changeWorker" />
                    </template>
                    <Tooltip title="Заменить">
                        <UserSwitchOutlined class="worker-icon yellow" />
                    </Tooltip>
                </Popover>
            </div>
        </section>
    </Card>
</template>