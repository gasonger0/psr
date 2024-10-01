<script setup>
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, FloatButtonGroup, Tooltip, Button, Popover, Select, notification, SelectOption } from 'ant-design-vue';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Loading from './loading.vue';
import dayjs from 'dayjs';
import { ColorPicker } from 'vue-color-kit';
import 'vue-color-kit/dist/vue-color-kit.css'
import { ForwardOutlined, LoginOutlined, PlusCircleOutlined, StopOutlined, InfoCircleOutlined, UserDeleteOutlined, UserSwitchOutlined } from '@ant-design/icons-vue';
</script>
<script>
export default {
    data() {
        return {
            lines: reactive([]),
            workers: reactive([]),
            products: reactive([]),
            slots: reactive([]),
            isLoading: ref(true),
            document: document,
            listenerSet: false,
            active: null,
            replacer: ref(null),
            contKey: 1,
            showList: false,
            cancel_reasons: [
                {
                    title: 'Ремонт',
                    value: 1
                }, {
                    title: 'Неправильное планирование',
                    value: 2
                }, {
                    title: 'Нехватка рабочих',
                    value: 3
                }, {
                    title: 'Недостаточная квалификация рабочих',
                    value: 4
                }, {
                    title: 'Отсутствие сырья',
                    value: 5
                }, {
                    title: 'Опоздание рабочих',
                    value: 6
                }, {
                    title: 'Корректировка предыдущих операций',
                    value: 7
                }, {
                    title: 'Иное',
                    value: 8
                }
            ]
        }
    },
    methods: {
        async getWorkers() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_workers')
                    .then(response => {
                        this.workers = response.data;
                        resolve(true);
                    })
                    .catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так");
                        reject(err);
                    });
            })
        },
        async processData() {
            return new Promise((resolve, reject) => {
                let curTime = new Date();
                let timeString =
                    (String(curTime.getHours()).length == 1 ? '0' + String(curTime.getHours()) : String(curTime.getHours()))
                    + ':' +
                    (String(curTime.getMinutes()).length == 1 ? '0' + String(curTime.getMinutes()) : String(curTime.getMinutes()))
                    + ':' +
                    (String(curTime.getSeconds()).length == 1 ? '0' + String(curTime.getSeconds()) : String(curTime.getSeconds()));
                this.slots.forEach(slot => {
                    if (slot.started_at < timeString && timeString < slot.ended_at) {
                        let worker = this.workers.find(worker => worker.worker_id == slot.worker_id);
                        worker.current_line_id = slot ? slot.line_id : false;
                        worker.current_slot = slot.slot_id;
                        slot.popover = ref(false);
                    }
                });
                this.workers.forEach((worker) => {
                    if ((worker.break_started_at <= timeString) && (timeString <= worker.break_ended_at)) {
                        worker.on_break = true;
                    }
                })

                this.isLoading = false;
                console.log('data');
                resolve(true);
            });
        },
        deleteWorker(record) {
            this.isLoading = true;
            axios.post('/api/delete_slot', { worker_id: record.worker_id })
                .then(response => {
                    this.isLoading = false;
                    // this.workers.splice(this.workers.indexOf(this.workers.find(el => el.worker_id == record.worker_id)), 1);
                    this.$emit('notify', 'success', 'Сотрудник ' + record.title + ' убран со смены');
                })
                .catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так");
                });
        },
        changeWorker(newWorker, oldWorker) {
            console.log(newWorker);
            console.log(oldWorker);
            if (this.workers.find(el => el.worker_id == newWorker.key).current_slot) {
                const notify = notification["warning"]({
                    description: 'Этот работник уже занят на другой линии'
                });
                oldWorker.popover = false;
                this.replacer = null;
                return;
            }
            if (newWorker.key == oldWorker.worker_id) {
                const notify = notification["warning"]({
                    description: 'Это тот же самый работник'
                });
                oldWorker.popover = false;
                this.replacer = null;
                return;
            }

            let fd = new FormData();
            fd.append('old_worker_id', oldWorker.worker_id);
            fd.append('slot_id', oldWorker.current_slot);
            fd.append('new_worker_id', newWorker.key);

            axios.post('/api/replace_worker',
                fd
            ).then(async (r) => {
                const notify = notification["success"]({
                    description: 'Работник заменён. Рабочая смена старого работника окончилась сейчас, а смена нового будет рассчитана от текущего момента до окончания смены.'
                });
                notify();

                oldWorker.popover = false;
                this.isLoading = true;
                oldWorker.current_slot = null;
                oldWorker.current_line_id = null
                await this.getSlots();
                await this.processData();
                this.calcCount();
                this.isLoading = false;
                this.replacer = null;
            }).catch((err) => {
                this.$emit('notify', 'error', "Что-то пошло не так");
            })
        },
        async getSlots() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_slots')
                    .then(response => {
                        this.slots = response.data;
                        resolve(true);
                    })
                    .catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так");
                        reject(err);
                    });
            });
        },
        calcCount() {
            this.lines = this.lines.map((line) => {
                line.count_current = this.workers.filter((wrkr) => wrkr.current_line_id == line.line_id).length;

                return line;
            });

        },
        updateData(ev) {
            console.log(ev);
            this.slots = ev.slots;
            this.workers = ev.workers;
        },
        getNextElement(cursorPosition, currentElement) {
            // Получаем объект с размерами и координатами
            const currentElementCoord = currentElement.getBoundingClientRect();
            // Находим вертикальную координату центра текущего элемента
            const currentElementCenter = currentElementCoord.y + currentElementCoord.height / 2;

            // Если курсор выше центра элемента, возвращаем текущий элемент
            // В ином случае — следующий DOM-элемент
            const nextElement = (cursorPosition < currentElementCenter) ?
                currentElement :
                currentElement.nextElementSibling;
            return nextElement;
        },
        sendStop(line) {
            axios.post('/api/down_line', 'id=' + line.line_id)
                .then(response => {
                    let f = this.lines.find(el => el.line_id == line.line_id);

                    if (!f.down_from) {
                        this.lines[this.lines.indexOf(f)].down_from = new Date();
                    } else {
                        this.lines[this.lines.indexOf(f)].down_from = null;
                    }
                    this.$emit('notify', 'success', 'Действие выполнено успешно')
                }).catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так");
                });
        },

        /*------------------- LINES START -------------------*/
        async getLines() {
            return new Promise((resolve, reject) => {
                this.isLoading = true;
                axios.get('/api/get_lines')
                    .then(response => {
                        this.lines = response.data;
                        this.lines.map((el) => {
                            el.edit = false;
                            el.color = ref(el.color);
                            el.showDelete = ref(false);
                            el.time = ref([dayjs(el.started_at, 'hh:mm:ss'), dayjs(el.ended_at, 'HH:mm:ss')]);
                            //el.time = ref(dayjs(el.started_at, 'hh:mm:ss'));
                            let curTime = new Date();

                            let timeString =
                                (String(curTime.getHours()).length == 1 ? '0' + String(curTime.getHours()) : String(curTime.getHours()))
                                + ':' +
                                (String(curTime.getMinutes()).length == 1 ? '0' + String(curTime.getMinutes()) : String(curTime.getMinutes()))
                                + ':' +
                                (String(curTime.getSeconds()).length == 1 ? '0' + String(curTime.getSeconds()) : String(curTime.getSeconds()));
                            if (timeString > el.ended_at) {
                                el.done = true;
                            } else {
                                el.done = false;
                            }
                            return el;
                        })
                        resolve(true);
                    })
                    .catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так");
                        reject(err);
                    });
            });
        },
        addLineFront() {
            this.lines.push({
                edit: true,
                time: ref([dayjs(), dayjs()]),
                title: 'Новая линия',
                workers_count: 0,
                line_id: -1
            });
            let x = this.document.querySelector('.lines-container');
            setTimeout(() => {
                x.scrollTo(
                    { left: x.clientWidth, behavior: 'smooth' }
                );
            }, 100);
            return;
        },
        saveLine(record) {
            let fd = new FormData();
            fd.append('line_id', record['line_id']);

            // if (record['line_id'] != -1) {
            //     fd.append('started_at', record.time.format('HH:mm:ss'));
            // } else {
            fd.append('started_at', record.time[0].format('HH:mm:ss'));
            fd.append('ended_at', record.time[1].format('HH:mm:ss'));
            // }
            fd.append('workers_count', record.workers_count);
            fd.append('color', record.color);
            fd.append('title', record.title);
            if (record.cancel_reason != null) {
                fd.append('cancel_reason', record.cancel_reason);
            }

            console.log(fd);
            axios.post('/api/save_line', fd)
                .then((response) => {
                    console.log(response);
                })
                .catch((err) => {
                    console.log(err);
                })
        },
        changeLine(line_id, worker_id) {
            // Остановить предыдущую смену этого работника (изменить время)
            // Создать новый слот
            let fd = new FormData();
            let worker = this.workers.find(el => el.worker_id == worker_id);
            console.log(worker);
            fd.append('new_line_id', line_id);
            fd.append('worker_id', worker_id);
            fd.append('old_line_id', worker.current_line_id);
            axios.post('/api/change_slot', fd)
                .then((response) => {
                    this.lines.find((el) => el.line_id == line_id).count_current += 1;
                    this.getSlots();
                    this.processData();
                    this.$emit('data-recieved', this.$data);

                    // this.contKey += 1;
                    // this.listenerSet = false;
                    // this.updated();
                })
                .catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так");
                });
        }
        /*-------------------- LINES END --------------------*/
    },
    async created() {
        await this.getLines();
        await this.getSlots();
        await this.getWorkers();
        await this.processData();

        this.calcCount();

        this.$emit('data-recieved', this.$data);
    },
    updated() {
        if (!this.listenerSet) {
            let draggable = this.document.querySelectorAll('.line_items');

            draggable.forEach(line => {
                line.addEventListener(`dragstart`, (ev) => {
                    ev.target.classList.add(`selected`);
                    this.active = ev.target;
                    console.log(ev.target)
                })

                line.addEventListener(`dragend`, (ev) => {
                    console.log(ev.target);
                    console.log(this.active);
                    if (ev.target.classList.contains('selected') && ev.target == this.active) {
                        // console.log(this.workers.find(el => el.worker_id == ev.target.dataset.id));
                        // let worker = this.workers.find(el => el.worker_id == ev.target.dataset.id);
                        // if (worker) {
                        //     worker.current_line_id = Number(ev.target.closest('.line').dataset.id);
                        // }
                        ev.target.classList.remove(`selected`);
                        this.changeLine(ev.target.closest('.line').dataset.id, ev.target.dataset.id);
                        //this.$emit('data-recieved', this.$data);
                    }
                });

                line.addEventListener('dragover', (ev) => {
                    ev.preventDefault();
                    const activeElement = document.querySelector('.selected');
                    const currentElement = ev.target;
                    const isMoveable = activeElement !== currentElement;

                    if (!isMoveable) {
                        return;
                    }

                    const nextElement = this.getNextElement(ev.clientY, currentElement);
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
                        line.append(activeElement);
                    } else {
                        if (nextElement.parentElement != line) {
                            line.append(activeElement);
                        } else {
                            line.insertBefore(activeElement, nextElement);
                        }
                    }
                })
            });
        }
        this.listenerSet = true;
        this.document.querySelector('.lines-container').scrollTo({ left: 0 });
    }
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список рабочих' : 'Скрыть' }}</Button>
    </div>
    <div class="lines-container" :key="contKey">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Не задействованы" :headStyle="{ 'background-color': 'white' }">
                <br>
            </Card>
            <section class="line_items">
                <Card :title="v.title" draggable="true" class="draggable-card"
                    v-for="(v, k) in workers.filter(el => el.current_line_id == null)"
                    :style="v.on_break ? 'opacity: 0.6' : ''" :data-id="v.worker_id"
                    @focus="() => { v.showDelete = true }" @mouseleave="() => { v.showDelete = false }">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
                    <span v-show="v.break_started_at && v.break_ended_at" style="height: fit-content;">
                        Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
                    </span>
                    <!-- <div style="display:flex; justify-content: space-between;align-items: center;">
                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" v-show="v.on_break">
                            <g id="Environment / Coffee">
                                <path id="Vector"
                                    d="M4 20H10.9433M10.9433 20H11.0567M10.9433 20C10.9622 20.0002 10.9811 20.0002 11 20.0002C11.0189 20.0002 11.0378 20.0002 11.0567 20M10.9433 20C7.1034 19.9695 4 16.8468 4 12.9998V8.92285C4 8.41305 4.41305 8 4.92285 8H17.0767C17.5865 8 18 8.41305 18 8.92285V9M11.0567 20H18M11.0567 20C14.8966 19.9695 18 16.8468 18 12.9998M18 9H19.5C20.8807 9 22 10.1193 22 11.5C22 12.8807 20.8807 14 19.5 14H18V12.9998M18 9V12.9998M15 3L14 5M12 3L11 5M9 3L8 5"
                                    stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                        </svg>
                    </div> -->
                </Card>
            </section>
        </div>
        <div class="line" v-for="line in lines" :data-id="line.line_id">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <template #title>
                    <div class="line_title" :data-id="line.line_id" v-show="!line.edit">
                        <b>{{ line.title }}</b>
                    </div>
                    <span style="color: white; font-weight:401;">Смена: {{ line.shift }}</span>
                    <Input v-show="line.edit" :data-id="line.line_id" class="line_title" v-model:value="line.title"
                        style="display: block;color:black;" />

                    <div style="display: flex;justify-content: space-between;align-items: center;">
                        <Switch v-model:checked="line.edit" checked-children="Редактирование"
                            un-checked-children="Просмотр" class="title-switch"
                            @change="(c, e) => { !c ? saveLine(line) : '' }" />
                        <Tooltip v-show="line.edit">
                            <template #title>
                                <ColorPicker theme="light" :color="line.color"
                                    @changeColor="(ev) => { line.color = ev.hex; console.log(line); }" />
                            </template>
                            <div style="width: 30px; height: 30px;border-radius: 5px; border: 2px solid white"
                                :style="'background-color:' + line.color" v-show="line.edit">
                            </div>
                        </Tooltip>
                        <div>
                            <Tooltip v-if="line.cancel_reason != null && !line.edit"
                                :title="'Время работы было изменено по причине: ' + cancel_reasons.find((el) => el.value == line.cancel_reason).title">
                                <InfoCircleOutlined style="color:#f48c05;font-size:24px;margin-right:12px;" />
                            </Tooltip>
                            <Tooltip :title="line.down_from ? 'Возобновить работу' : 'Остановить работу'">
                                <ForwardOutlined @click="sendStop(line)" v-if="line.down_from"
                                    style="height:min-content;color:#82ff82;font-size:22px;" />
                                <StopOutlined @click="sendStop(line)" v-else
                                    style="height:min-content;color:#ff4d4f;font-size:22px;" />
                            </Tooltip>
                        </div>
                    </div>
                </template>
                <template v-if="line.edit">
                    <div style="width:100%; max-width:400px;">
                        <span
                            style="display: flex; justify-content: space-between; margin-bottom:10px;align-items: center;">
                            <span style="height:fit-content;">Необходимо:&nbsp;&nbsp;</span>
                            <Input v-model:value="line.workers_count" type="number" />
                        </span>
                        <!-- <span style="display: flex; justify-content: space-between; align-items: center;"
                            v-if="line.line_id != -1">
                            <span>Работает с:</span>
                            <TimePicker v-model:value="line.time" format="HH:mm" :showTime="true" :allowClear="true"
                                type="time" :showDate="false" style="width:fit-content;" />
                        </span> -->
                        <span>Время работы:</span><br />
                        <TimeRangePicker v-model:value="line.time" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false" style="width:fit-content;" />
                        <Select v-model:value="line.cancel_reason" placeholder="Причина переноса старта"
                            style="margin-top: 10px; width: 100%;">
                            <SelectOption v-for="i in cancel_reasons" v-model:value="i.value">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                    </div>
                </template>
                <template v-else>
                    <div class="line_sub-title">
                        <span :style="line.count_current < line.workers_count ? 'color:#ff4d4f;' : ''">Необходимо
                            работников: {{ line.workers_count ? line.workers_count : 'без ограничений'
                            }}</span>
                        <br>
                        <span>Всего работников на линии: {{ line.count_current ? line.count_current : '0' }}</span>
                    </div>
                </template>
            </Card>
            <section class="line_items">
                <Card :title="v.title" draggable="true" class="draggable-card"
                    v-for="(v, k) in workers.filter(el => el.current_line_id == line.line_id)"
                    :style="v.on_break ? 'opacity: 0.6' : ''" :data-id="v.worker_id"
                    @focus="() => { v.showDelete = true }" @mouseleave="() => { v.showDelete = false }">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
                    <div style="display:flex; justify-content: space-between;align-items: center;">
                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" v-show="v.on_break">
                            <g id="Environment / Coffee">
                                <path id="Vector"
                                    d="M4 20H10.9433M10.9433 20H11.0567M10.9433 20C10.9622 20.0002 10.9811 20.0002 11 20.0002C11.0189 20.0002 11.0378 20.0002 11.0567 20M10.9433 20C7.1034 19.9695 4 16.8468 4 12.9998V8.92285C4 8.41305 4.41305 8 4.92285 8H17.0767C17.5865 8 18 8.41305 18 8.92285V9M11.0567 20H18M11.0567 20C14.8966 19.9695 18 16.8468 18 12.9998M18 9H19.5C20.8807 9 22 10.1193 22 11.5C22 12.8807 20.8807 14 19.5 14H18V12.9998M18 9V12.9998M15 3L14 5M12 3L11 5M9 3L8 5"
                                    stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                        </svg>
                        <span v-show="v.break_started_at && v.break_ended_at" style="height: fit-content;">
                            Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
                        </span>
                        <div style="display: flex; gap:5px;">
                            <Tooltip title="Убрать со смены">
                                <UserDeleteOutlined
                                    style="color:#ff4d4f;padding:5px;border: 2px solid #ff4d4f;font-size:25px;border-radius:20px;"
                                    @click="deleteWorker(v)" />
                            </Tooltip>
                            <Popover v-model:open="v.popover" trigger="click" placement="right">
                                <template #content>
                                    <Select style="width:20vw;" v-model:value="replacer"
                                        :options="workers.map(el => { return { key: el.worker_id, label: el.title, value: el.title } })"
                                        :showSearch="true" @select="(n, ev) => changeWorker(ev, v)"></Select>
                                </template>
                                <Tooltip title="Заменить">
                                    <UserSwitchOutlined
                                        style="color:#f48c05;padding:5px;border: 2px solid #f48c05;font-size:25px;border-radius:20px;" />
                                </Tooltip>
                            </Popover>
                        </div>
                    </div>
                </Card>
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
        <!-- <FloatButton type="default" @click="addWorkerFront">
            <template #tooltip>
                <div>Добавить сотрудника</div>
            </template>
            <template #icon>
                <UserAddOutlined />
            </template>
        </FloatButton> -->
        <BackTop />
    </FloatButtonGroup>
</template>