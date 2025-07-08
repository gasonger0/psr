<script setup lang="ts">
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, FloatButtonGroup, Tooltip, Button, Popover, Select, notification, SelectOption, Popconfirm, Modal, TimePicker, RadioGroup, RadioButton, Checkbox, Form, FormItem } from 'ant-design-vue';
import { ref, reactive, Ref, watch, computed, onBeforeMount, TemplateRef } from 'vue';
import Loading from './../../deprecated/loading.vue';
import dayjs from 'dayjs';
import { ColorPicker } from 'vue-color-kit';
import 'vue-color-kit/dist/vue-color-kit.css';
import { ForwardOutlined, LoginOutlined, PlusCircleOutlined, StopOutlined, InfoCircleOutlined, UserDeleteOutlined, UserSwitchOutlined, UserAddOutlined, RightOutlined, LeftOutlined, PrinterOutlined } from '@ant-design/icons-vue';
import { useWorkersStore, WorkerForm, WorkerInfo } from '../../store/workers';
import { getNextElement, getTimeString } from '../../functions';
import ItemCard from './card.vue';
import { cancelReasons, positions } from '../../store/dicts';
import ScrollButtons from '../common/scrollButtons.vue';
import { LineInfo, useLinesStore } from '../../store/lines';
import { useResponsiblesStore } from '../../store/responsibles';
import { DefaultOptionType } from 'ant-design-vue/es/vc-cascader';
import { useWorkerSlotsStore, WorkerSlot } from '../../store/workerSlots';

const linesStore = useLinesStore();
const workersStore = useWorkersStore();
const responsiblesStore = useResponsiblesStore();
const workersSlotsStore = useWorkerSlotsStore();

// TODO добавить сущности и хранилки к ним
// const products = useProductsStore();

//TODO добавитьт загрузку данных в хранилки
let newWorker: WorkerInfo = reactive({
    title: '',
    company: ''
});
let showNewWorker = ref(false);
let linesContainer: Ref<TemplateRef | null> = ref(null);
let active: Ref<HTMLElement | null> = ref(null);
const isLoading = ref(false); //TODO придумать, чё делать
const showList = ref(false);

const processData = async () => {
    return new Promise((resolve, reject) => {
        let ts = getTimeString();
        workersSlotsStore.slots.forEach((slot: WorkerSlot) => {
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
const getLineWorkerLimit = (line: LineInfo) => {
    return line.count_current! < line.workers_count ? 'color:#ff4d4f;' : '';
}
const formatLineResponsible = (line: LineInfo) => {
    let arr: string[] = [];
    if (line.master) {
        let f = responsiblesStore.getById(line.master!);
        if (f) {
            let n = f.title.split(' ');
            arr.push(n[0] + ' ' + n[1][0] + '.' + ', ' + f.position);
        }
    }

    if (line.engineer) {
        let f = responsiblesStore.getById(line.master!);
        if (f) {
            let n = f.title.split(' ');
            arr.push(n[0] + ' ' + n[1][0] + '.' + ', ' + f.position);
        }
    }
    return arr.join('\n');
}

const addLineFront = () => {
    linesStore.add();
}

const addWorker = async () => {
    if (await workersStore._addWorker(newWorker)) {
        newWorker = {
            title: '',
            company: ''
        };
    }
}
const saveLine = (c: boolean, line: LineInfo) => {
    if (c) {
        return
    }
    if (line.line_id) {
        linesStore._update(line);
    } else {
        linesStore._add(line);
    }
}
const sendStop = (line: LineInfo, reason?: DefaultOptionType) => {
    linesStore._sendStop(line, reason ? reason.label : undefined);
}
// TODO требует проверки 
const scrollToTop = () => {
    let x = linesContainer.value;
    if (!x) return;
    setTimeout(() => {
        x.scrollTo(
            { left: x.scrollWidth, behavior: 'smooth' }
        );
    }, 100);
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
    await workersSlotsStore._load();
    await processData();

    console.log(linesStore.lines);

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
                workersStore._changeWorker(
                    Number(
                        target.closest('.line')!.getAttribute('data-id')
                    ),
                    Number(
                        target.getAttribute('data-id')
                    )
                );
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
            console.log(ev);
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
                        <!-- <Input name="title" :rules="[{required: true, message: 'Введите ФИО сотрудника'}]"/> -->
                        <!-- <span>Имя:</span> -->
                        <!-- <Input v-model:value="newWorkerFields.title" /> -->
                        <!-- <br> -->
                        <!-- <span>Компания:</span> -->
                        <!-- <Input v-model:value="newWorkerFields.company" /> -->
                        <!-- <br> -->
                        <!-- <span>Обед:</span>
                         
                        TODO Проверить отображение и подумать насчёт указания времени обеда

                        TODO Подумать о вынесении конструктора форма в отдельный компонент? 

                        <TimePicker v-model:value="newWorkerFields.break[0]" format="HH:mm" :showTime="true"
                            :allowClear="true" type="time" :showDate="false" style="width:fit-content;" />
                        <TimePicker v-model:value="newWorkerFields.break[1]" format="HH:mm" :showTime="true"
                            :allowClear="true" type="time" :showDate="false" style="width:fit-content;" /> -->
                        <Button type="primary" style="width:100%" html-type="submit">
                            Сохранить
                        </Button>
                    </Form>
                </Card>
                <ItemCard :card-data="v" v-for="(v, k) in workersStore.getByLine(null)" />
                <!-- <Card :title="v.title" draggable="true" class="draggable-card"
                    v-for="(v, k) in workers.getByLine(null)"
                    :style="v.on_break ? 'opacity: 0.6' : ''" :data-id="v.worker_id" :key="v.worker_id"
                    @focus="() => { v.showDelete = true }" @mouseleave="() => { v.showDelete = false }">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
<span v-if="v.break_started_at && v.break_ended_at" style="height: fit-content;">
    Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
</span>
</Card> -->
            </section>
        </div>
        <div class="line" v-for="line in linesStore.lines" :data-id="line.line_id!" :class="getLineClass(line)">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <template #title>
                    <div class="line_title" :data-id="line.line_id" v-show="!line.edit">
                        <b style="font-weight:700;">{{ line.title }}</b>
                        <br>
                        <span style="font-weight:400;display: block;width: 100%;white-space: collapse;">{{
                            line.extra_title }}</span>
                    </div>
                    <Input v-show="line.edit" :data-id="line.line_id" class="line_title" v-model:value="line.title"
                        style="display: block;color:black;" />
                    <Input v-show="line.edit" class="line_title" v-model:value="line.extra_title"
                        style="display: block;color:black;" placeholder="Производительность" />

                    <div style="display: flex;justify-content: space-between;align-items: center;">
                        <Switch v-model:checked="line.edit" checked-children="Редактирование"
                            un-checked-children="Просмотр" class="title-switch"
                            @change="(c, e) => saveLine(c as boolean, line)" />
                        <Tooltip v-show="line.edit">
                            <template #title>
                                <ColorPicker theme="light" :color="line.color"
                                    @changeColor="(ev) => { line.color = ev.hex; }" />
                            </template>
                            <div style="width: 30px; height: 30px;border-radius: 5px; border: 2px solid white"
                                :style="'background-color:' + line.color" v-show="line.edit">
                            </div>
                        </Tooltip>
                        <div>
                            <Tooltip v-if="line.cancel_reason != null && !line.edit"
                                :title="'Время работы было изменено по причине: ' + cancelReasons.find((el) => el.value == line.cancel_reason)!.label">
                                <InfoCircleOutlined style="color:#f48c05;font-size:24px;margin-right:12px;" />
                            </Tooltip>
                            <Tooltip :title="line.down_from ? 'Возобновить работу' : 'Остановить работу'">
                                <ForwardOutlined @click="sendStop(line)" v-if="line.down_from"
                                    style="height:min-content;color:#82ff82;font-size:22px;" />
                                <Popconfirm v-else :showCancel="false" id="popover" placement="right">
                                    <template #title>
                                        <Select placeholder="Причина остановки" style="margin-top: 10px; width: 100%;"
                                            @change="(value, option) => sendStop(line, option)">
                                            <SelectOption v-for="i in cancelReasons" v-model:value="i.value">
                                                <Tooltip :title="i.label">
                                                    {{ i.label }}
                                                </Tooltip>
                                            </SelectOption>
                                        </Select>
                                    </template>
                                    <StopOutlined style="height:min-content;color:#ff4d4f;font-size:22px;" />
                                </Popconfirm>
                            </Tooltip>
                        </div>
                    </div>
                </template>
                <template v-if="line.edit">
                    <div style="width:100%; max-width:400px;">
                        <span
                            style="display: flex; justify-content: space-between; margin-bottom:10px;align-items: center;">
                            <span style="height:fit-content;">Необходимо:&nbsp;&nbsp;</span>
                            <Input v-model:value="line.workers_count" type="number" placeholder="10 человек" />
                        </span>
                        <span>Время работы:</span><br />
                        <div style="display: flex; justify-content: space-between;">
                            <TimePicker v-model:value="line.work_time.started_at" format="HH:mm" :showTime="true"
                                :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                            <TimePicker v-model:value="line.work_time.ended_at" format="HH:mm" :showTime="true"
                                :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                        </div>
                        <Select v-model:value="line.cancel_reason" placeholder="Причина переноса старта"
                            style="margin-top: 10px; width: 100%;">
                            <SelectOption v-for="i in cancelReasons" v-model:value="i.value">
                                {{ i.label }}
                            </SelectOption>
                        </Select>
                        <span>Подготовительное время(мин):</span><Input v-model:value="line.prep_time"
                            placeholder="0" />
                        <span>Заключительное время(мин):</span><Input v-model:value="line.after_time" placeholder="0" />
                        <br>
                        <br>
                        <RadioGroup v-model:value="line.type_id">
                            <RadioButton value="1">Варка</RadioButton>
                            <RadioButton value="2">Упаковка</RadioButton>
                        </RadioGroup>
                        <span>Ответственные:</span>
                        <Select v-model:value="line.master" style="width:100%;">
                            <SelectOption v-for="i in responsiblesStore.responsibles" v-model:value="i.responsible_id">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                        <Select v-model:value="line.engineer" style="width:100%;margin-top:10px;">
                            <SelectOption v-for="i in responsiblesStore.responsibles" v-model:value="i.responsible_id">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                        <Checkbox v-model:checked="line.detector.has_detector" style="margin-top:10px;">
                            Установить металодетектор
                        </Checkbox>
                        <div v-if="line.detector.has_detector">
                            <div style="display: flex; justify-content: space-between;">
                                <TimePicker v-model:value="line.detector.detector_start" format="HH:mm" :showTime="true"
                                    :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                                <TimePicker v-model:value="line.detector.detector_end" format="HH:mm" :showTime="true"
                                    :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                            </div>
                            <!-- <TimeRangePicker v-model:value="line.detector_time" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false" style="width:fit-content;" /> -->
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="line_sub-title">
                        <span :style="getLineWorkerLimit(line)">
                            Необходимо работников: {{ line.workers_count ?? 'без ограничений' }}</span>
                        <br>
                        <span>Всего работников на линии: {{ line.count_current ? line.count_current : '0' }}</span>
                        <br>
                        <span v-show="line.engineer || line.master">
                            Ответственные:
                            <br />
                            {{ formatLineResponsible(line) }}
                        </span>
                    </div>
                </template>
            </Card>
            <section class="line_items">
                <Card v-for="(v, k) in workersStore.getByLine(line.line_id!)" :cardData="v" />
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