<script setup lang="ts">
import { colons, hardwares, packHardwares, productsTabs } from '@/store/dicts';
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { ProductInfo, useProductsStore } from '@/store/products';
import { ProductPlan, usePlansStore } from '@/store/productsPlans';
import { ProductSlot, useProductsSlotsStore } from '@/store/productsSlots';
import { CheckboxGroup, Checkbox, Modal, RadioGroup, RadioButton, InputNumber, TimePicker, Tooltip, Radio, RadioChangeEvent } from 'ant-design-vue';
import { CheckboxOptionType, CheckboxValueType } from 'ant-design-vue/es/checkbox/interface';
import { computed, onBeforeMount, onUpdated, ref, Ref, watch } from 'vue';

const props = defineProps({
    data: {
        type: Object as () => ProductPlan
    }
})
const modal = useModalsStore();
const slotsStore = useProductsSlotsStore();
const productsStore = useProductsStore();
const plansStore = usePlansStore();
const linesStore = useLinesStore();

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
const selRadioValue: Ref<number | null> = ref();
/**
 * Показывать/скрывать упаковку
 */
const showPack: Ref<boolean> = ref(false);
/**
 * Выбранная колонка варки
 */
const colon = ref();
/**
 * Выбранное оборудование
 */
const hardware: Ref<number | null> = ref(null);
/**
 * Все слоты по данной линии и типу
 */
const slots: Ref<ProductSlot[]> = ref([]);

const perfomance: Ref<number> = ref(0);

const changeTime = () => {
    if (!props.data.ended_at) {
        return;
    }
    props.data.ended_at = props.data.started_at
        .add(time.value, 'hour')
        .add(slot.value.type_id == 1 ? 10 : 15, 'minute');
    console.log('Bug:', props.data);
    // props.data.ended_at = props.data.started_at.add(time.value, 'hour');
    // props.data.ended_at = props.data.ended_at.add(slot.value.type_id == 1 ? 10 : 15, 'minute');

};
const handleHardware = () => {
    // TODO чёто не уверен, что даст выбрать производительность при выборе из selection...
    selection.value = [];

    // Ищем новый слот с таким оборудованием и линией 
    let newSlot = slots.value.filter((n: ProductSlot) =>
        n.hardware == hardware.value
    );
    // Если такого нет, берём тот, что без оборудования
    if (!newSlot) {
        newSlot = slots.value.filter((n: ProductSlot) =>
            n.hardware == null
        );
    }
    console.log(newSlot, hardware, slot, slots);
    if (newSlot.length == 0) {
        return;
    }

    // Если подходящих слотов несколько, даём пользователю выбрать производительность
    if (newSlot.length > 1) {
        selection.value = newSlot;
        selRadioValue.value = newSlot[0].product_slot_id;
        slot.value = newSlot[0];
    } else {
        slot.value = newSlot[0];
    }
    perfomance.value = newSlot[0].perfomance;

    // Коррекция производительности для упаковки на ЗМ
    let perf = [4, 5].includes(hardware.value) ? 143.5 : 287;
    if (perf && newSlot[0].type_id == 2) {
        perfomance.value += perf;
    }

    console.log(perfomance);
    changeTime();
};

const time = computed(() => {
    return props.data.amount *
        eval(product.value.amount2parts) *
        eval(product.value.parts2kg) /
        perfomance.value;  // Время в часах
});
const boils = computed(() => {
    return (props.data.amount *
        eval(product.value.kg2boil) *
        eval(product.value.amount2parts) *
        eval(product.value.parts2kg)
    ).toFixed(2);
});
const showError = computed(() => {
    let show = props.data.ended_at > line.value.work_time.ended_at;
    if (show) {
        return `
            ВНИМАНИЕ! Продукция будет изготавливаться дольше, чем работает линия!
            \n
            Скорректируте объём изготовления продукции или время работы линии`;
    }
})
const getPackOptions = () => {
    packOptions.value = slotsStore.getPack(product.value.product_id).map((el: ProductSlot) => {
        let slot = slotsStore.getById(el.product_slot_id);
        return {
            label: `<${productsTabs[slot.type_id]}> ${linesStore.getByID(el.line_id).title} (${el.perfomance} кг/ч)`,
            value: el.product_slot_id
        } as CheckboxOptionType;
    }).filter(el => el.value != slot.value.product_slot_id);
}
const addPlan = async () => {
    let p = [];
    for (let i in packs.value) {
        p.push(packs.value[i]);
    }
    // TODO костыль как будто?
    props.data.colon = ref(Number(colon.value[0]));
    if (props.data.plan_product_id) {
        await plansStore._update(props.data, p);
    } else {
        await plansStore._create(props.data, p);
    }
    prepareClose();
    emit('save');
    modal.close('plan');
}

const exit = () => {
    prepareClose();
    emit('cancel');
    modal.close('plan');
}

const prepareClose = () => {
    showPack.value = false,
        packOptions.value = [],
        packs.value = [],
        colon.value = null,
        slot.value = null,
        slots.value = [],
        product.value = null,
        line.value = null;
}

onUpdated(async () => {
    if (props.data != undefined) {
        slot.value = slotsStore.getById(props.data.slot_id);
        product.value = productsStore.getByID(slot.value.product_id);
        slots.value = slotsStore.slots.filter((el: ProductSlot) =>
            el.product_id == product.value.product_id &&
            el.line_id == slot.value.line_id &&
            el.type_id == slot.value.type_id
        );
        getPackOptions();
        line.value = linesStore.getByID(slot.value.line_id);
        handleHardware();
        if (props.data.plan_product_id) {
            // Редактирование, надо упаковки копировать
            packs.value = plansStore.plans.filter(el => el.parent == props.data.plan_product_id).map(i => i.slot_id);
            // console.log(props.data.colon.value);
            if (props.data.colon) {
                colon.value = props.data.colon;
            }
        }
        if (packs.value) {
            showPack.value = true;
        }
    }
});
const emit = defineEmits(['save', 'cancel']);
// TODO стили
</script>
<template>
    <Modal v-model:open="modal.visibility['plan']" @ok="addPlan" @cancel="exit" okText="Да" cancelText="Нет"
        v-if="data && product && line && slot">
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
            <h3>Варочная колонка: </h3>
            <RadioGroup v-model:value="colon">
                <RadioButton v-for="(v, k) in colons" :value="k">
                    {{ v }}
                </RadioButton>
            </RadioGroup>
            <br>
            <h3>Оборудование:</h3>
            <RadioGroup v-model:value="hardware" @change="handleHardware">
                <RadioButton v-for="i in hardwares" :value="i.value">
                    {{ i.label }}
                </RadioButton>
            </RadioGroup>
            <br>
            <RadioGroup v-if="selection" @change="handleHardware" v-model:value="selRadioValue">
                <RadioButton v-for="v in selection" :value="v.product_slot_id" :key="v.product_slot_id">
                    {{ v.perfomance }}
                </RadioButton>
            </RadioGroup>
            <!-- <div>Выбранная производительность: {{ active.perfomance }}</div> -->
        </div>
        <div v-if="slot.type_id == 2">
            <RadioGroup v-model:value="hardware" @change="handleHardware">
                <RadioButton v-for="i in packHardwares" :value="i.value">
                    <Tooltip :title="i.title">{{ i.label }}</Tooltip>
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
                <CheckboxGroup v-model:value="packs" :options="packOptions" class="pack-options">
                </CheckboxGroup>
            </div>
        </div>
        <br>
        <span>С учётом производительности линии для данного продукта, время изготовления составит
            <b>{{ time.toFixed(2) }}</b>ч.
        </span>
        <br>
        <span v-if="time >= 24" style="color:#ff4d4f">
            ВНИМАНИЕ! Данная продукция будет изготавливаться больше суток!
        </span>
        <br>
        <span>Работа по данной продукции закончится в <b>{{ data.ended_at.format('HH:mm') }}</b></span>
        <br>
        <span style="color:#ff4d4f">
            {{ showError }}
        </span>
    </Modal>
</template>