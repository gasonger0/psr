<script setup lang="ts">
import { BackTop, Card, FloatButton, Input, FloatButtonGroup, Button, Form, FormItem } from 'ant-design-vue';
import { ref, Ref, watch, computed, onBeforeMount, TemplateRef } from 'vue';
import * as dayjs from "dayjs";
import 'vue-color-kit/dist/vue-color-kit.css';
import { LoginOutlined, PlusCircleOutlined, UserAddOutlined, PrinterOutlined } from '@ant-design/icons-vue';
import { useWorkersStore, WorkerForm, WorkerInfo } from '@stores/workers';
import { getNextElement, getTimeString, notify, scrollToTop } from '@/functions';
import ItemCard from '@boards/workers/card.vue';
import ScrollButtons from '@/components/common/scrollButtons.vue';
import { LineInfo, useLinesStore } from '@stores/lines';
import { useResponsiblesStore } from '@stores/responsibles';
import { useWorkerSlotsStore } from '@stores/workerSlots';
import LineForm from '@/components/common/lineForm.vue';

const linesStore = useLinesStore();
const workersStore = useWorkersStore();
const workerSlotsStore = useWorkerSlotsStore();

let newWorker: Ref<WorkerInfo> = ref({
    title: '',
    company: '',
    isEditing: true
});
let showNewWorker: Ref<boolean> = ref(false);
let linesContainer: Ref<HTMLElement | null> = ref();
let active: Ref<HTMLElement | null> = ref(null);
const showList = ref(false);
const workerRefs = ref<Record<number, InstanceType<typeof ItemCard>>>({})

const processData = async () => {
    return new Promise((resolve, reject) => {
        let ts = getTimeString();
        workerSlotsStore.slots.forEach(slot => {
            if (slot.started_at <= ts && ts <= slot.ended_at) {
                let worker = workersStore.getByID(slot.worker_id);
                worker!.current_line_id = slot ? slot.line_id : null;
                worker!.current_slot_id = slot.slot_id;
                slot.popover = ref(false);
            }
        });

        resolve(true);
    });
};
const setWorkerRef = (el: any, workerId: number) => {
    if (el) workerRefs.value[workerId] = el;
};
const handleCardChange = (id: number) => {
    if (workerRefs.value[id]) {
        workerRefs.value[id].$forceUpdate();
    }
}

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
    if (await workersStore._create(newWorker.value)) {
        newWorker.value = {
            title: '',
            company: '',
            isEditing: true
        };
    }
}

const print_graph = () => {
    window.open('/api/print_slots', '_blank');
}

const recalcCounters = () => {
    linesStore.lines.forEach((line: LineInfo) => {
        line.count_current = workersStore.getByLine(line.line_id!)?.length;
    });
}

watch(
    () => workerSlotsStore.slots,
    (newWorkers) => {
        recalcCounters()
    },
    { deep: true }
);

onBeforeMount(async () => {
    await processData();

    recalcCounters();
    let draggable = document.querySelectorAll('.line_items');
    draggable.forEach(line => {
        line.addEventListener(`dragstart`, (ev: Event) => {
            if (!ev) {
                return;
            }
            let target = ev.target as HTMLElement;
            scrollToTop(linesContainer);
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
                let worker = workersStore.getByID(
                    Number(
                        target.getAttribute('data-id')
                    )
                );
                if (!worker) {
                    notify('warning', 'Такого сотрудника не существует');
                    return;
                }
                let line_id = line.parentElement!.getAttribute('data-id');
                if (typeof worker?.current_line_id == 'number') {
                    workerSlotsStore._change(worker, Number(line_id));
                    handleCardChange(worker.worker_id);
                } else {
                    workerSlotsStore._create(worker, Number(line_id));
                    handleCardChange(worker.worker_id);
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
                <ItemCard v-for="(v, k) in workersStore.getByLine(line.line_id!)" :cardData="v"
                    :ref="(el) => setWorkerRef(el, v.worker_id)" />
            </section>
        </div>
    </div>
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
        <FloatButton type="default" @click="showNewWorker = true; showList = true;">
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