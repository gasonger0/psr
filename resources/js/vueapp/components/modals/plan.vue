<script setup lang="ts">
import { colons, hardwares, packHardwares } from '@/store/dicts';
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { ProductInfo, useProductsStore } from '@/store/products';
import { ProductPlan, usePlansStore } from '@/store/productsPlans';
import { ProductSlot, useProductsSlotsStore } from '@/store/productsSlots';
import { CheckboxGroup, Checkbox, Modal, RadioGroup, RadioButton, InputNumber, TimePicker, Tooltip } from 'ant-design-vue';
import { CheckboxOptionType, CheckboxValueType } from 'ant-design-vue/es/checkbox/interface';
import { computed, onBeforeMount, onUpdated, ref, Ref } from 'vue';

const props = defineProps({
    data: {
        type: Object as () => ProductPlan
    }
})
const modal = useModalsStore();
const slotsStore = useProductsSlotsStore();

/** 
 * Активный слот 
 * */
const slot: Ref<ProductSlot> = ref();
/**
 * Активный продукт
 */
const product: Ref<ProductInfo> = ref();
/**
 * Активная линия
 */
const line: Ref<LineInfo> = ref();
/**
 * Опции упаквки
 */
const packOptions: Ref<CheckboxOptionType[]> = ref([]);
/**
 * Выбранные слоты упаковки ГП
 */
const packs: Ref<CheckboxValueType[]> = ref([]);
/**
 * Вариации с одним оборудованием, но разной производительностью
 */
const selection: Ref<ProductSlot[]> = ref([]);
/**
 * Выбранная производительность из вариаций
 */
const selRadioValue: Ref<number> = ref();
/**
 * Показывать/скрывать упаковку
 */
const showPack: Ref<boolean> = ref(false);
/**
 * Выбранная колонка варки
 */
const colon: Ref<CheckboxValueType[]> = ref();


const changeTime = () => {
    props.data.ended_at = props.data.started_at.add(time.value, 'hour');
    props.data.ended_at = props.data.ended_at.add(slot.value.type_id == 1 ? 10 : 15, 'minute');

    let plans = usePlansStore().getByLine(line.value.line_id).map(el => el.started_at);
    for (let i = 0; i < plans.length; i++) {
        if (plans[i] >= props.data.started_at && plans[i + 1] < props.data.started_at) {
            props.data.position = i;
        }
    }
};
const handleHardware = () => {
    selection.value = [];
    let hw = slot.value.hardware;

    // Ищем новый слот с таким оборудованием и линией 
    let newSlot = slotsStore.slots.filter((n: ProductSlot) => {
        return n.line_id == line.value.line_id && n.hardware == hw;
    });
    // Если такого нет, берём тот, что без оборудования
    if (!newSlot) {
        newSlot = slotsStore.slots.filter((n: ProductSlot) => {
            return n.line_id == line.value.line_id && n.hardware == null && n.type_id == slot.value.type_id;
        });
    }
    if (!newSlot) {
        return;
    }

    // Если подходящих слотов несколько, даём пользователю выбрать производительность
    if (newSlot.length > 1) {
        selection.value = newSlot;
        selRadioValue.value = newSlot[0].product_slot_id;
        return;
    }
    slot.value = newSlot[0];
    // Коррекция производительности для упаковки на ЗМ
    let perf = [4, 5].includes(hw) ? 143.5 : 287;
    if (perf && slot.value.type_id == 2) {
        slot.value.perfomance += perf;
    }
    changeTime();
};
const time = computed(() => {
    return props.data.amount * 
        eval(product.value.amount2parts) * 
        eval(product.value.parts2kg) / 
        slot.value.perfomance;  // Время в часах
});
const boils = computed(() => {
    return (props.data.amount * eval(product.value.kg2boil)).toFixed(2);
});
const showError = computed(() => {
    let show = props.data.ended_at > line.value.work_time.ended_at;
    if (show) {
        return `<span style="color:#ff4d4f">
            ВНИМАНИЕ! Продукция будет изготавливаться дольше, чем работает линия!
            <br />
            Скорректируте объём изготовления продукции или время работы линии
        </span>`;
    }
})
const getPackOptions = () => {
    packOptions.value = slotsStore.getByTypeAndProductID(product.value.product_id, 2).map((el: ProductSlot) => {
        return {
            label: useLinesStore().getByID(el.line_id).title,
            value: el.product_slot_id
        } as CheckboxOptionType;
    });
}
const addPlan = () => {

}

const exit = () => {
    modal.close('plan');
}
onBeforeMount(() => {
    if (props.data) {
        handleHardware()
    }
});

onUpdated(() => {
    if (props.data != undefined) {
        slot.value = slotsStore.getById(props.data.slot_id);
        product.value = useProductsStore().getByID(slot.value.product_id);
        getPackOptions();
        line.value = useLinesStore().getByID(slot.value.line_id);
    }
});
</script>
<template>
    <Modal v-model:open="modal.visibility['plan']" @ok="addPlan()" @cancel="exit" okText="Да" cancelText="Нет"
        v-if="data">
        <span>Это действие поставит работу <b>{{ product.title }}</b> на линии <b>{{ line.title }}</b>.</span>
        <br>
        <div style="display: flex;justify-content: space-between;margin: 14px 0px;">
            <b style="font-size: 16px">Объём изготовления:</b>
            <InputNumber v-model:value="data.amount" @change="changeTime" />
        </div>
        <div style="display:flex;justify-content: space-between;margin: 14px 0px;">
            <div><b style="font-size: 16px">Подготовительное время:</b> {{ line.prep_time }} мин.</div>
            <div><b style="font-size: 16px">Заключительное время:</b> {{ line.after_time }} мин.</div>
        </div>
        <div style="display: flex;justify-content: space-between;margin: 14px 0px;">
            <b style="font-size: 16px">Время начала:</b>
            <TimePicker v-model:value="data.started_at" @change="changeTime" format="HH:mm" />
        </div>
        <div v-if="slot.type_id == 1">
            <span>Количество варок: {{ boils }}</span>
            <h3>Колонка: </h3>
            <CheckboxGroup v-model:value="colon">
                <Checkbox v-for="i in colons" :value="i">
                    {{ colons[i] }}
                </Checkbox>
            </CheckboxGroup>
            <br>
            <h3>Оборудование:</h3>
            <RadioGroup v-model:value="data.hardware" @change="handleHardware()">
                <RadioButton :value="null">Нет</RadioButton>
                <RadioButton v-for="i in hardwares" :value="i.value">
                    {{ i.label }}
                </RadioButton>
            </RadioGroup>
            <br>
            <br>
            <RadioGroup v-if="selection" @change="handleHardware" v-model:value="selRadioValue">
                <RadioButton v-for="v in selection" :value="v.product_slot_id" :key="v.product_slot_id">
                    {{ v.perfomance }}
                </RadioButton>
            </RadioGroup>
            <!-- <div>Выбранная производительность: {{ active.perfomance }}</div> -->
        </div>
        <div v-if="slot.type_id == 2">
            <RadioGroup v-model:value="data.hardware" @change="handleHardware">
                <RadioButton :value="null">Нет</RadioButton>
                <RadioButton v-for="i in packHardwares" :value="i.value">
                    <Tooltip :title="i.tooltip">{{ i.label }}</Tooltip>
                </RadioButton>
            </RadioGroup>
        </div>
        <Checkbox v-model:checked="showPack" v-if="slot.type_id != 2 && packOptions">
            Сгененрировать план упаковки
        </Checkbox>
        <br>
        <div v-if="showPack">
            <div>
                <span>Упаковать через </span>
                <InputNumber v-model:value="data.delay" placeholder="30" />
                <span> мин.</span>
                <br />
                <CheckboxGroup v-model:value="packs" :options="packOptions" style="flex-direction:column">
                </CheckboxGroup>
            </div>
        </div>
        <br>
        <span>С учётом производительности линии для данного продукта, время изготовления составит
            <b>{{ time }}</b>ч.
        </span>
        <span v-if="time >= 24" style="color:#ff4d4f">
            ВНИМАНИЕ! Данная продукция будет изготавливаться больше суток!
        </span>
        <br>
        <span>Работа по данной продукции закончится в <b>{{ data.ended_at.format('HH:mm') }}</b></span>
        <br>
        {{ showError }}
    </Modal>
</template>