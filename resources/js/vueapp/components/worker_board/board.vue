<script setup lang="ts">
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, FloatButtonGroup, Tooltip, Button, Popover, Select, notification, SelectOption, Popconfirm, Modal, TimePicker, RadioGroup, RadioButton, Checkbox, Form, FormItem } from 'ant-design-vue';
import { ref, reactive, Ref, watch, computed, onBeforeMount, TemplateRef } from 'vue';
import Loading from './../../deprecated/loading.vue';
import dayjs from 'dayjs';
import { ColorPicker } from 'vue-color-kit';
import 'vue-color-kit/dist/vue-color-kit.css';
import { ForwardOutlined, LoginOutlined, PlusCircleOutlined, StopOutlined, InfoCircleOutlined, UserDeleteOutlined, UserSwitchOutlined, UserAddOutlined, RightOutlined, LeftOutlined, PrinterOutlined } from '@ant-design/icons-vue';
import { useWorkersStore, WorkerForm, WorkerInfo } from '../../store/workers';
import { getNextElement, getTimeString, notify } from '../../functions';
import ItemCard from './card.vue';
import { cancelReasons, positions } from '../../store/dicts';
import ScrollButtons from '../common/scrollButtons.vue';
import { LineInfo, useLinesStore } from '../../store/lines';
import { useResponsiblesStore } from '../../store/responsibles';
import { DefaultOptionType } from 'ant-design-vue/es/vc-cascader';
import { useWorkerSlotsStore, WorkerSlot } from '../../store/workerSlots';
import LineForm from '../common/lineForm.vue';

const linesStore = useLinesStore();
const workersStore = useWorkersStore();
const responsiblesStore = useResponsiblesStore();
const workerSlotsStore = useWorkerSlotsStore();

// TODO добавить сущности и хранилки к ним
// const products = useProductsStore();

//TODO добавитьт загрузку данных в хранилки
let newWorker: WorkerInfo = reactive({
    title: '',
    company: ''
});
let showNewWorker = ref(false);
let linesContainer = ref();
let active: Ref<HTMLElement | null> = ref(null);
const isLoading = ref(false); //TODO придумать, чё делать
const showList = ref(false);

const processData = async () => {
    return new Promise((resolve, reject) => {
        let ts = getTimeString();
        workerSlotsStore.slots.forEach(slot => {
            if (slot.started_at <= ts && ts <= slot.ended_at) {
                let worker = workersStore.getById(slot.worker_id);
                worker!.current_line_id = slot ? slot.line_id : null;
                worker!.current_slot_id = slot.slot_id;
                slot.popover = ref(false);
            }
        });

        isLoading.value = false;
        resolve(true);
    });
};

const newListText = computed(() => {
    return showList.value ? 'Скрыть' : 'Показать список рабочих';
});
const getLineClass = (line: LineInfo) => {
    return linesStore.getIfDone(line) ? 'done-line' : '';
}

const addLineFront = () => {
    linesStore.add();
}

const addWorker = async () => {
    if (await workersStore._create(newWorker)) {
        newWorker = {
            title: '',
            company: ''
        };
    }
}
// TODO требует проверки 
const scrollToTop = () => {
    let x: TemplateRef | null = linesContainer.value;
    if (x == null) {
        return;
    } else {
        // TODO сомнения
        setTimeout((x) => {
            x.scrollTo(
                { left: x.scrollWidth, behavior: 'smooth' }
            );
        }, 100);
    }
};

const print_graph = () => {
    window.open('/api/print_slots', '_blank');
}

const recalcCounters = () => {
    linesStore.lines.forEach((line: LineInfo) => {
        line.count_current = workersStore.getByLine(line.line_id!)?.length;
    });
}

watch(
    () => workersStore.workers,
    (newWorkers) => {
        recalcCounters()
    },
    { deep: true }
);

onBeforeMount(async () => {
    dayjs.locale('ru-ru');
    // TODO перенести в app
    await linesStore._load();
    await workersStore._load();
    await responsiblesStore._load();
    await workerSlotsStore._load();
    await processData();

    recalcCounters();
    let draggable = document.querySelectorAll('.line_items');
    draggable.forEach(line => {
        line.addEventListener(`dragstart`, (ev: Event) => {
            if (!ev) {
                return;
            }
            let target = ev.target as HTMLElement;
            scrollToTop();
            target.classList.add(`selected`);
            document.querySelectorAll('.done-line').forEach(el => {
                el.classList.toggle('hidden');
            })
            active.value = target;
        })

        line.addEventListener(`dragend`, (ev) => {
            document.querySelectorAll('.done-line').forEach(el => {
                el.classList.toggle('hidden');
            })
            let target = ev.target as Element;
            if (target.classList.contains('selected') && ev.target == active.value) {
                target.classList.remove(`selected`);
                let worker = workersStore.getById(
                    Number(
                        target.getAttribute('data-id')
                    )
                );
                if (!worker) {
                    notify('warning', 'Такого сотрудника не существует');
                    return;
                }
                let line_id = line.parentElement!.getAttribute('data-id'); // TODO проверить!
                if (typeof worker?.current_line_id == 'number') {
                    workerSlotsStore._change(worker, Number(line_id));
                } else {
                    workerSlotsStore._create(worker, Number(line_id));
                }
            }
        });

        line.addEventListener('dragover', (ev: Event) => {
            ev.preventDefault();
            const activeElement = document.querySelector('.selected');
            const currentElement = ev.target;
            const isMoveable = activeElement !== currentElement;

            if (!isMoveable) {
                return;
            }
            // TODO огромные сомнения? было ev.clientY
            const nextElement = getNextElement(
                Number(
                    (ev.target as Element).getAttribute('clientY')
                ),
                currentElement as Element
            );
            // Проверяем, нужно ли менять элементы местами
            if (
                nextElement &&
                activeElement === nextElement.previousElementSibling ||
                activeElement === nextElement
            ) {
                // Если нет, выходим из функции, чтобы избежать лишних изменений в DOM
                return;
            }

            const lastElement = line.lastElementChild;
            if (nextElement == null) {
                line.append(activeElement as HTMLElement);
            } else {
                if (nextElement.parentElement != line) {
                    line.append(activeElement as HTMLElement);
                } else {
                    line.insertBefore(activeElement as HTMLElement, nextElement);
                }
            }
        })
    });
    document.querySelector('.lines-container')!.scrollTo({ left: 0 });
})
// dayjs.locale('ru-ru');
</script>
<template>
    <div class="lines-toolbar">
        <Button type="dashed" @click="() => showList = !showList">
            {{ newListText }}
        </Button>
        <Button type="default" @click="print_graph">
            <PrinterOutlined />
            Распечатать график
        </Button>
    </div>
    <!-- TODO: Придумать, как ре-рендерить карточки при оюновлении данных? -->
    <div class="lines-container" ref="linesContainer">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Не задействованы" :headStyle="{ 'background-color': 'white' }">
                <br>
            </Card>
            <section class="line_items">
                <Card v-show="showNewWorker" draggable="false" class="draggable-card">
                    <Form :model="newWorker" @finish="addWorker">
                        <FormItem v-for="field in WorkerForm" :name="field.name" :rules="field.rules"
                            :label="field.label">
                            <Input v-model:value="newWorker[field.name]" />
                        </FormItem>
                        <!-- TODO Проверить отображение и подумать насчёт указания времени обеда -->
                        <!-- TODO Подумать о вынесении конструктора форма в отдельный компонент?  -->
                        <Button type="primary" style="width:100%" html-type="submit">
                            Сохранить
                        </Button>
                    </Form>
                </Card>
                <ItemCard :card-data="v" v-for="(v, k) in workersStore.getByLine(null)" />
            </section>
        </div>
        <div class="line" v-for="line in linesStore.lines" :data-id="line.line_id!" :class="getLineClass(line)">
            <LineForm :data="line" />
            <section class="line_items">
                <ItemCard v-for="(v, k) in workersStore.getByLine(line.line_id!)" :cardData="v" />
            </section>
        </div>
    </div>
    <Loading :open="isLoading" />
    <FloatButtonGroup trigger="hover" type="primary">
        <template #tooltip>
            <div>Добавить..</div>
        </template>
        <template #icon>
            <PlusCircleOutlined />
        </template>
        <FloatButton type="default" @click="addLineFront">
            <template #tooltip>
                <div>Добавить линию</div>
            </template>
            <template #icon>
                <LoginOutlined />
            </template>
        </FloatButton>
        <FloatButton type="default" @click="showNewWorker = true">
            <template #tooltip>
                <div>Добавить сотрудника</div>
            </template>
            <template #icon>
                <UserAddOutlined />
            </template>
        </FloatButton>
        <BackTop />
    </FloatButtonGroup>
    <ScrollButtons :containerRef="linesContainer" :speed="280" />
</template>