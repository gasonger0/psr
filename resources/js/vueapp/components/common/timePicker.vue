<script setup lang="ts">
import { WorkerSlot } from '@/store/workerSlots';
import { MinusCircleOutlined, PlusCircleOutlined } from '@ant-design/icons-vue';
import { TimePicker } from 'ant-design-vue';

const props = defineProps<{
    model: WorkerSlot,
    last?: boolean,
    dels?: boolean
}>();
const emit = defineEmits<{
    change: [slot: WorkerSlot],
    add: [],
    del: []
}>();

const change = () => {
    emit("change", props.model);
}
const add = () => emit("add");
const del = () => emit("del");

defineExpose(props);
</script>
<template v-if="props.model">
    <TimePicker v-model:value="props.model.started_at" format="HH:mm" :showTime="true" :allowClear="true" type="time"
        :showDate="false" size="small" class="timepicker" @change="change" />
    <span> - </span>
    <TimePicker v-model:value="props.model.ended_at" format="HH:mm" :showTime="true" :allowClear="true" type="time"
        :showDate="false" size="small" class="timepicker" @change="change" />
    <PlusCircleOutlined class="icon" style="color:grey;cursor:pointer;" v-if="last" @click="add" />
    <MinusCircleOutlined class="icon" style="color:grey;cursor:pointer;" v-if="dels" @click=del />
</template>