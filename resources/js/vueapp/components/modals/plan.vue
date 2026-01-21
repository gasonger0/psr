<script setup lang="ts">
import { colons, hardwares, packHardwares, productsTabs } from '@/store/dicts';
import { LineInfo, useLinesStore } from '@/store/lines';
import { useModalsStore } from '@/store/modal';
import { ProductInfo, useProductsStore } from '@/store/products';
import { ProductPlan, usePlansStore } from '@/store/productsPlans';
import { ProductSlot, useProductsSlotsStore } from '@/store/productsSlots';
import {
    Button,
    CheckboxGroup,
    Checkbox,
    Modal,
    RadioGroup,
    RadioButton,
    InputNumber,
    TimePicker,
    Tooltip,
    Alert,
    Spin
} from 'ant-design-vue';
import { CheckboxOptionType, CheckboxValueType } from 'ant-design-vue/es/checkbox/interface';
import { computed, ref, watch, onUnmounted, nextTick, reactive, toRefs } from 'vue';
import dayjs from 'dayjs';
import { useZMCategories } from '@/functions';
import { RadioGroupOptionType } from 'ant-design-vue/es/radio/interface';

interface ModalState {
    slot: ProductSlot | null;
    product: ProductInfo | null;
    line: LineInfo | null;
    packOptions: CheckboxOptionType[];
    zmOptions: { label: string, value: number }[];
    packs: CheckboxValueType[];
    // TODO: если по МТ согласуют, то это можно нахер убирать
    selection: ProductSlot[];
    selRadioValue: number | null;
    showPack: boolean;
    colon: any;
    hardware: number | null;
    slots: ProductSlot[];
    perfomance: number;
    isLoading: boolean;
    error: string | null;
    isInitialized: boolean;
}

const props = defineProps<{
    data: ProductPlan | null;
}>();

const emit = defineEmits<{
    save: [];
    cancel: [];
}>();

// Stores
const modal = useModalsStore();
const slotsStore = useProductsSlotsStore();
const productsStore = useProductsStore();
const plansStore = usePlansStore();
const linesStore = useLinesStore();

// State
const state = reactive<ModalState>({
    slot: null,
    product: null,
    line: null,
    packOptions: [],
    zmOptions: [],
    packs: [],
    selection: [],
    selRadioValue: null,
    showPack: false,
    colon: 1,
    hardware: null,
    slots: [],
    perfomance: 0,
    isLoading: false,
    error: null,
    isInitialized: false
});

// Local refs for form
const localPlanData = ref<ProductPlan | null>(null);
const abortController = ref<AbortController | null>(null);

// Computed properties
const time = computed(() => {
    if (!localPlanData.value?.amount || !state.product || !state.perfomance) return 0;

    return localPlanData.value.amount *
        eval(state.product.amount2parts) *
        eval(state.product.parts2kg) /
        state.perfomance;
});

const boils = computed(() => {
    if (!localPlanData.value?.amount || !state.product) return '0.00';

    return (localPlanData.value.amount *
        eval(state.product.kg2boil) *
        eval(state.product.amount2parts) *
        eval(state.product.parts2kg)
    ).toFixed(2);
});

const showError = computed(() => {
    if (!localPlanData.value || !state.line) return null;

    const show = localPlanData.value.ended_at > state.line.work_time.ended_at;
    if (show) {
        return {
            type: 'warning' as const,
            message: 'ВНИМАНИЕ! Продукция будет изготавливаться дольше, чем работает линия!',
            description: 'Скорректируйте объём изготовления продукции или время работы линии'
        };
    }
    return null;
});

const timeWarning = computed(() => {
    if (!time.value) return null;

    if (time.value >= 24) {
        return {
            type: 'warning' as const,
            message: 'ВНИМАНИЕ! Данная продукция будет изготавливаться больше суток!'
        };
    }
    return null;
});

// Methods
const changeTime = () => {
    if (!localPlanData.value?.started_at || !state.slot) return;

    const startTime = dayjs(localPlanData.value.started_at);
    if (!startTime.isValid()) return;

    // Вычисляем время окончания
    const hoursToAdd = time.value || 0;
    const minutesToAdd = state.slot.type_id === 1 ? 10 : 15;

    const newEndTime = startTime
        .add(hoursToAdd, 'hour')
        .add(minutesToAdd, 'minute');

    if (localPlanData.value) {
        localPlanData.value.ended_at = newEndTime;
    }
};

const handleHardware = () => {
    // if (!state.slots.length || state.hardware === null) return;

    state.selection = [];

    // Ищем слоты с указанным оборудованием
    let newSlots = state.slots.filter((slot: ProductSlot) =>
        slot.hardware === state.hardware
    );

    // Если не нашли, берем без оборудования
    if (newSlots.length === 0) {
        newSlots = state.slots.filter((slot: ProductSlot) =>
            slot.hardware === null
        );
    }

    console.log('New slot for hardware', newSlots)

    if (newSlots.length === 0) {
        if (state.slot) {
            state.perfomance = state.slot.perfomance;
        }

        // // Коррекция производительности для упаковки на ЗМ
        // if (state.slot?.type_id === 2) {
        //     // Без Завёрток должно быть как у слота по умолчанию
        //     console.log("no new slots. Default slot is:", state.slot);

        //     if ([4, 5, 6].includes(state.hardware!)) {
        //         const perf = (state.hardware === 4 || state.hardware === 5) ? 143.5 : 287;
        //         state.perfomance = perf;
        //     }
        // }

        changeTime();
        return;
    }

    // Если несколько слотов, предлагаем выбрать
    if (newSlots.length > 1) {
        state.selection = newSlots;
        state.selRadioValue = newSlots[0].product_slot_id;
        state.slot = { ...newSlots[0] };
    } else {
        state.slot = { ...newSlots[0] };
    }

    state.perfomance = newSlots[0].perfomance;

    // Коррекция производительности для упаковки на ЗМ
    // if ([4, 5, 6].includes(state.hardware!) && newSlots[0].type_id === 2) {
    //     const perf = state.hardware === 4 || state.hardware === 5 ? 143.5 : 287;
    //     state.perfomance += perf;
    // }
    console.log(state.perfomance);

    changeTime();
};

const handleSelection = () => {
    if (!state.selection.length || state.selRadioValue === null) return;

    const selectedSlot = state.selection.find(
        slot => slot.product_slot_id === state.selRadioValue
    );

    if (selectedSlot) {
        state.slot = { ...selectedSlot };
        state.perfomance = selectedSlot.perfomance;
        changeTime();
    }
};

const getPackOptions = async () => {
    if (!state.product) return;

    try {
        const packSlots = slotsStore.getPack(state.product.product_id);
        state.packOptions = packSlots
            .map((el: ProductSlot) => {
                const slot = slotsStore.getById(el.product_slot_id);
                const line = linesStore.getByID(el.line_id);
                return {
                    label: `<${productsTabs[slot.type_id]}> ${line?.title || ''} (${el.perfomance} кг/ч)`,
                    value: el.product_slot_id
                } as CheckboxOptionType;
            })
            .filter(el => state.slot && el.value !== state.slot.product_slot_id);

        // Получение категорий и обработка для one shot
        if (useZMCategories.includes(state.product.category_id)) {
            state.zmOptions = [1, 2, 3].map((el: number) => {
                return {
                    label: `<Упаковка> One Shot ЗМ ${el == 3 ? '№1 и №2' : '№' + el} (${el == 3 ? 287 : 143.5} кг/ч)`,
                    value: el
                };
            })
        }
    } catch (error) {
        console.error('Error getting pack options:', error);
    }
};

const loadSlotData = async (slotId: number) => {
    try {
        const storeSlot = slotsStore.getById(slotId);
        if (!storeSlot) {
            throw new Error('Слот не найден');
        }

        state.slot = { ...storeSlot };
        state.slots = slotsStore.slots.filter((el: ProductSlot) =>
            el.product_id === storeSlot.product_id &&
            el.line_id === storeSlot.line_id &&
            el.type_id === storeSlot.type_id
        );

        return storeSlot;
    } catch (error) {
        console.error('Error loading slot:', error);
        throw error;
    }
};

const loadProductData = async (productId: number) => {
    try {
        const storeProduct = productsStore.getByID(productId);
        if (!storeProduct) {
            throw new Error('Продукт не найден');
        }

        state.product = { ...storeProduct };
        return storeProduct;
    } catch (error) {
        console.error('Error loading product:', error);
        throw error;
    }
};

const loadLineData = async (lineId: number) => {
    try {
        const storeLine = linesStore.getByID(lineId);
        if (!storeLine) {
            throw new Error('Линия не найдена');
        }

        state.line = { ...storeLine };
        return storeLine;
    } catch (error) {
        console.error('Error loading line:', error);
        throw error;
    }
};

const initializeData = async () => {
    if (!props.data || state.isInitialized) return;

    state.isLoading = true;
    state.error = null;

    try {
        // Отменяем предыдущие запросы
        abortController.value?.abort();
        abortController.value = new AbortController();

        // Копируем данные для локальной работы
        localPlanData.value = {
            ...props.data,
            started_at: dayjs(props.data.started_at),
            ended_at: dayjs(props.data.ended_at)
        };

        // Загружаем основные данные
        const slot = await loadSlotData(props.data.slot_id);

        await Promise.all([
            loadProductData(slot.product_id),
            loadLineData(slot.line_id),
            getPackOptions()
        ]);

        state.perfomance = slot.perfomance;

        console.log('slot!', slot);
        // Устанавливаем оборудование если есть
        if (slot.hardware && slot.hardware > 0) {
            state.hardware = Number(slot.hardware);
            handleHardware();
        } else {
            // Базовый расчет времени
            changeTime();
        }

        // Загружаем данные для редактирования
        if (props.data.plan_product_id) {
            state.packs = plansStore.plans
                .filter(el => el.parent === props.data!.plan_product_id)
                .map(i => i.slot_id);

            if (props.data.colon) {
                state.colon = props.data.colon;
            } else {
                state.colon = 1;
            }
        }

        state.showPack = state.packs.length > 0;
        state.isInitialized = true;

    } catch (error: any) {
        if (error.name !== 'AbortError') {
            state.error = error.message || 'Ошибка загрузки данных';
            console.error('Initialize error:', error);
        }
    } finally {
        state.isLoading = false;
    }
};

const reloadData = async () => {
    state.isInitialized = false;
    await initializeData();
};

const addPlan = async () => {
    if (!localPlanData.value || !state.slot) return;

    state.isLoading = true;

    try {
        // const planData: ProductPlan = 
        // localPlanData.value
        //   colon: ref(state.slot.type_id === 1 ? Number(state.colon?.[0]) : 1)


        const packIds = state.showPack ? [...state.packs] as number[] : [];
        if (localPlanData.value.plan_product_id) {
            await plansStore._update(localPlanData.value, packIds);
        } else {
            await plansStore._create(localPlanData.value, packIds);
        }

        emit('save');
        closeModal();

    } catch (error) {
        state.error = 'Ошибка сохранения плана';
        console.error('Save error:', error);
    } finally {
        state.isLoading = false;
    }
};

const exit = () => {
    emit('cancel');
    closeModal();
};

const resetState = () => {
    Object.assign(state, {
        slot: null,
        product: null,
        line: null,
        packOptions: [],
        zmOptions: [],
        packs: [],
        selection: [],
        selRadioValue: null,
        showPack: false,
        colon: null,
        hardware: null,
        slots: [],
        perfomance: 0,
        isLoading: false,
        error: null,
        isInitialized: false
    });

    delete localPlanData.value;
};

const closeModal = () => {
    resetState();
    modal.close('plan');
};

// Watchers
watch(() => props.data, (newData) => {
    if (newData) {
        initializeData();
    } else {
        resetState();
    }
}, { immediate: true });

watch(() => modal.visibility['plan'], (isVisible) => {
    if (!isVisible) {
        abortController.value?.abort();
        resetState();
    }
});

watch(() => localPlanData.value?.amount, () => {
    console.log('changed amount');
    console.log(localPlanData, state);
    changeTime();
}, { deep: true });

watch(() => localPlanData.value?.started_at, () => {
    changeTime();
}, { deep: true });

// Lifecycle
onUnmounted(() => {
    abortController.value?.abort();
    resetState();
});
</script>

<template>
    <Modal v-model:open="modal.visibility['plan']" @ok="addPlan" @cancel="exit"
        :ok-button-props="{ loading: state.isLoading }" :cancel-button-props="{ disabled: state.isLoading }"
        okText="Сохранить" cancelText="Отмена" :closable="!state.isLoading" :maskClosable="!state.isLoading"
        width="650px">
        <template #title>
            <span v-if="state.product && state.line">
                {{ props.data?.plan_product_id ? 'Редактирование' : 'Создание' }} плана
            </span>
        </template>

        <Spin :spinning="state.isLoading">
            <div class="modal-content"
                v-if="!state.error && localPlanData && state.product && state.line && state.slot">
                <!-- Основная информация -->
                <div class="info-section">
                    <p>
                        Это действие поставит работу <b>{{ state.product.title }}</b>
                        на линии <b>{{ state.line.title }}</b>.
                    </p>
                </div>

                <!-- Объем изготовления -->
                <div class="form-section">
                    <div class="form-row">
                        <span class="label">Объём изготовления:</span>
                        <InputNumber v-model:value="localPlanData.amount" :min="0" :step="1" :disabled="state.isLoading"
                            style="width: 120px" />
                    </div>

                    <!-- Время начала -->
                    <div class="form-row">
                        <span class="label">Время начала:</span>
                        <TimePicker v-model:value="localPlanData.started_at" format="HH:mm" :disabled="state.isLoading"
                            style="width: 120px" />
                    </div>

                    <!-- Время работы линии -->
                    <div class="form-row time-info">
                        <div>
                            <b>Подготовительное время: {{ state.line.prep_time }} мин.</b>
                        </div>
                        <div>
                            <b>Заключительное время: {{ state.line.after_time }} мин.</b>
                        </div>
                    </div>
                </div>

                <!-- Специфичные настройки для варки -->
                <div v-if="state.slot.type_id === 1" class="form-section">
                    <div class="form-row">
                        <span>Количество варок: <b>{{ boils }}</b></span>
                    </div>

                    <div class="form-group">
                        <h4>Варочная колонка:</h4>
                        <RadioGroup v-model:value="state.colon" :disabled="state.isLoading">
                            <RadioButton v-for="(v, k) in colons" :value="k" :key="k">
                                {{ v }}
                            </RadioButton>
                        </RadioGroup>
                    </div>

                    <div class="form-group">
                        <h4>Оборудование:</h4>
                        <RadioGroup v-model:value="state.hardware" @change="handleHardware" :disabled="state.isLoading">
                            <RadioButton v-for="i in hardwares" :value="i.value" :key="i.value">
                                {{ i.label }}
                            </RadioButton>
                        </RadioGroup>
                    </div>

                    <!-- Выбор производительности -->
                    <!-- Нафиг, если согласуют -->
                    <div v-if="state.selection.length" class="form-group">
                        <h4>Выберите производительность:</h4>
                        <RadioGroup v-model:value="state.selRadioValue" @change="handleSelection"
                            :disabled="state.isLoading">
                            <RadioButton v-for="v in state.selection" :value="v.product_slot_id"
                                :key="v.product_slot_id">
                                {{ v.perfomance }} кг/ч
                            </RadioButton>
                        </RadioGroup>
                    </div>
                </div>

                <!-- Специфичные настройки для упаковки -->
                <div v-if="state.slot.type_id === 2 && state.line.title.toLowerCase().includes('one shot')"
                    class="form-section">
                    <div class="form-group">
                        <h4>Оборудование упаковки:</h4>
                        <RadioGroup v-model:value="state.hardware" @change="handleHardware" :disabled="state.isLoading">
                            <RadioButton v-for="i in packHardwares" :value="i.value" :key="i.value">
                                <Tooltip :title="i.title">{{ i.label }}</Tooltip>
                            </RadioButton>
                        </RadioGroup>
                    </div>
                </div>

                <!-- Настройки упаковки -->
                <div v-if="state.slot.type_id !== 2 && (state.packOptions.length || state.zmOptions.length)"
                    class="form-section">
                    <Checkbox v-model:checked="state.showPack" :disabled="state.isLoading">
                        Сгенерировать план упаковки
                    </Checkbox>

                    <div v-if="state.showPack" class="pack-section">
                        <div class="form-row">
                            <span>Упаковать через</span>
                            <div>
                                <InputNumber v-model:value="localPlanData.delay" :min="0" :step="5"
                                    :disabled="state.isLoading" style="width: 80px; margin: 0 8px" />
                                <span>мин.</span>
                            </div>
                        </div>

                        <div class="pack-options">
                            <RadioGroup v-model:value="state.hardware" :option="state.zmOptions" :disabled="state.isLoading"
                                v-if="state.zmOptions.length > 0" />

                            <CheckboxGroup v-model:value="state.packs" :options="state.packOptions"
                                :disabled="state.isLoading" />
                        </div>
                    </div>
                </div>

                <!-- Расчет времени -->
                <div class="calculation-section">
                    <p>
                        С учётом производительности линии для данного продукта,
                        время изготовления составит <b>{{ time.toFixed(2) }}</b> ч.
                    </p>

                    <p>
                        Работа по данной продукции закончится в
                        <b>{{ localPlanData.ended_at?.format('HH:mm') || '--:--' }}</b>
                    </p>
                </div>

                <!-- Предупреждения -->
                <div class="warnings-section">
                    <Alert v-if="timeWarning" :type="timeWarning.type" :message="timeWarning.message" show-icon
                        banner />

                    <Alert v-if="showError" :type="showError.type" :message="showError.message"
                        :description="showError.description" show-icon banner />
                </div>
            </div>

            <!-- Состояние ошибки -->
            <div v-else-if="state.error" class="error-state">
                <Alert type="error" :message="state.error" show-icon />
                <Button @click="reloadData" style="margin-top: 16px">
                    Повторить попытку
                </Button>
            </div>
        </Spin>
    </Modal>
</template>

<style scoped>
.modal-content {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 4px;
}

.info-section {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.form-section {
    margin: 16px 0;
    padding: 16px;
    border: 1px solid #f0f0f0;
    border-radius: 4px;
    background-color: #fafafa;
}

.form-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 12px 0;
}

.form-row.time-info {
    justify-content: space-between;
}

.form-row.time-info>div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.label {
    font-weight: 500;
    font-size: 14px;
    color: #333;
}

.form-group {
    margin: 16px 0;
}

.form-group h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 500;
}

.pack-section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e8e8e8;
}

.pack-options {
    margin-top: 12px;
}

.pack-options :deep(.ant-checkbox-group) {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.calculation-section {
    margin: 20px 0;
    padding: 16px;
    background-color: #f6ffed;
    border: 1px solid #b7eb8f;
    border-radius: 4px;
}

.calculation-section p {
    margin: 8px 0;
}

.warnings-section {
    margin-top: 16px;
}

.warnings-section :deep(.ant-alert) {
    margin-bottom: 8px;
}

.error-state {
    text-align: center;
    padding: 40px 20px;
}

/* Scrollbar styling */
.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>