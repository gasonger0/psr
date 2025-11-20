<script setup lang="ts">
import { BackTop, Card, FloatButton, Input, FloatButtonGroup, Button, Form, FormItem, Switch } from 'ant-design-vue';
import { ref, Ref, watch, computed, onBeforeMount, TemplateRef, onMounted } from 'vue';
import 'vue-color-kit/dist/vue-color-kit.css';
import { LoginOutlined, PlusCircleOutlined, UserAddOutlined, PrinterOutlined } from '@ant-design/icons-vue';
import { useWorkersStore, WorkerForm, WorkerInfo } from '@stores/workers';
import { getNextElement, getTimeString, notify, scrollToTop } from '@/functions';
import ItemCard from '@boards/workers/card.vue';
import ScrollButtons from '@/components/common/scrollButtons.vue';
import { LineInfo, useLinesStore } from '@stores/lines';
import { useWorkerSlotsStore } from '@stores/workerSlots';
import LineForm from '@/components/common/lineForm.vue';
import { useCompaniesStore } from '@/store/companies';

const linesStore = useLinesStore();
const workersStore = useWorkersStore();
const workerSlotsStore = useWorkerSlotsStore();

let newWorker: Ref<WorkerInfo> = ref({
    title: '',
    company: useCompaniesStore().getByID(1),
    isEditing: true
});
let showNewWorker: Ref<boolean> = ref(false);
let linesContainer: Ref<HTMLElement | null> = ref();
let active: Ref<HTMLElement | null> = ref(null);
const showList = ref(false);
const workerRefs = ref<Record<number, InstanceType<typeof ItemCard>>>({});
const linesRefs = ref({});
const monitorInterval: Ref<number> = ref(null);
const editMode: Ref<boolean> = ref(false);

const setWorkerRef = (el: any, workerId: number) => {
    if (el) workerRefs.value[workerId] = el;
};
const handleCardChange = (id: number, worker_id: number) => {
    if (workerRefs[worker_id]) {
        workerRefs[worker_id].$forceUpdate();
    }
    if (linesRefs[id]) {
        linesRefs[id] += 1;
    }
}

const newListText = computed(() => {
    return showList.value ? 'Скрыть' : 'Показать список рабочих';
});
const getLineClass = (line: LineInfo) => {
    return linesStore.getIfDone(line) ? 'done-line ' : ''
}

const addLineFront = () => {
    linesStore.add();
    let cont = document.querySelector('.lines-container');
    setTimeout(() => {
        cont ? cont.scrollTo({
            left: cont.scrollWidth,
            behavior: 'smooth'
        }) : null, 100
    });
}

const addWorker = async () => {
    if (await workersStore._create(newWorker.value)) {
        newWorker.value = {
            title: '',
            company: useCompaniesStore().getByID(1),
            isEditing: true
        };
    }
}

const print_graph = () => {
    window.open('/api/workers_slots/print', '_blank');
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

const monitor = async () => {
    let current = workerSlotsStore.getCurrent();
    workersStore.workers.forEach(worker => {
        let curSlot = current.find(i => i.worker_id == worker.worker_id);
        if (!curSlot) {
            worker.current_line_id = null;
            worker.current_slot_id = null;
        } else if (curSlot.slot_id != worker.current_slot_id) {
            worker.current_slot_id = curSlot.slot_id;
            worker.current_line_id = curSlot.line_id;
        }
        worker.on_break = workersStore.calcBreak(worker).value;
    });
};

const handleEditMode = () => {
    if (editMode.value) {
        clearInterval(monitorInterval.value);
    } else {
        monitorInterval.value = setInterval(monitor, 3000);
    }
}

onMounted(async () => {
    linesStore.lines.forEach((el: LineInfo) => {
        linesRefs[el.line_id] = 1;
    });
    recalcCounters();
    monitorInterval.value = setInterval(monitor, 3000);
    let draggable = document.querySelectorAll('.line_items');
    draggable.forEach(line => {
        line.addEventListener(`dragstart`, (ev: Event) => {
            if (!ev) {
                return;
            }
            let target = ev.target as HTMLElement;
            scrollToTop(linesContainer);
            target.classList.add(`selected`);
            if (!editMode.value) {
                document.querySelectorAll('.done-line').forEach(el => {
                    el.classList.add('hidden-hard');
                });
            }
            active.value = target;
        })

        line.addEventListener(`dragend`, (ev) => {
            if (!editMode.value) {
                document.querySelectorAll('.done-line').forEach(el => {
                    el.classList.toggle('hidden-hard');
                });
            }
            let target = ev.target as HTMLElement;
            if (target.classList.contains('selected') && ev.target == active.value) {
                target.classList.remove(`selected`);
                let worker = workersStore.getByID(
                    Number(
                        target.dataset.id
                    )
                );
                if (!worker) {
                    notify('warning', 'Такого сотрудника не существует');
                    return;
                }
                let line_id = line.parentElement!.dataset.id;
                if (typeof worker?.current_line_id == 'number') {
                    workerSlotsStore._change(worker, Number(line_id));
                    handleCardChange(Number(line_id), worker.worker_id);
                } else {
                    workerSlotsStore._create(worker, Number(line_id), editMode.value);
                    handleCardChange(Number(line_id), worker.worker_id);
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
});

const emit = defineEmits(['ready']);
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
        <Switch v-model:checked="editMode" checked-children="Планирование" un-checked-children="Коррекция" @change="handleEditMode"/>
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
                        <Button type="primary" style="width:100%" html-type="submit">
                            Сохранить
                        </Button>
                    </Form>
                </Card>
                <ItemCard :card-data="v" v-for="(v, k) in workersStore.getByLine(null)" />
            </section>
        </div>
        <div class="line" v-for="line in linesStore.lines" :data-id="line.line_id!" :class="getLineClass(line)"
            :key="linesRefs[line.line_id]">
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