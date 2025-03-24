<script setup>
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, FloatButtonGroup, Tooltip, Button, Popover, Select, notification, SelectOption, Popconfirm, Modal, TimePicker, RadioGroup, RadioButton, Checkbox } from 'ant-design-vue';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Loading from './loading.vue';
import dayjs from 'dayjs';
import { ColorPicker } from 'vue-color-kit';
import 'vue-color-kit/dist/vue-color-kit.css';
import { ForwardOutlined, LoginOutlined, PlusCircleOutlined, StopOutlined, InfoCircleOutlined, UserDeleteOutlined, UserSwitchOutlined, UserAddOutlined, RightOutlined, LeftOutlined, PrinterOutlined } from '@ant-design/icons-vue';
</script>
<script>
dayjs.locale('ru-ru');
export default {
    data() {
        return {
            lines: reactive([]),
            workers: reactive([]),
            products: reactive([]),
            slots: reactive([]),
            isLoading: ref(true),
            isScrolling: ref(false),
            document: document,
            listenerSet: false,
            active: null,
            replacer: ref(null),
            contKey: 1,
            responsible: [],
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
            ],
            newWorker: ref(false),
            newWorkerFields: {
                worker_id: null,
                title: null,
                break: [dayjs(), dayjs()]
            },
            positions: {
                1: 'Начальник смены',
                2: 'Мастер смены',
                3: 'Мастер варочного участка',
                4: 'Инженер',
                5: 'Наладчик'
            }
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
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                        reject(err);
                    });
            })
        },
        async getResponsible() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_responsible')
                    .then(response => {
                        this.responsible = response.data.map(el => {
                            el.position = this.positions[el.position];
                            return el;
                        });
                        resolve(true);
                    })
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
                // console.log('data');
                resolve(true);
            });
        },
        deleteWorker(record, del) {
            this.isLoading = true;
            axios.post('/api/delete_slot', { worker_id: record.worker_id, slot_id: record.current_slot, delete: del })
                .then(response => {
                    this.isLoading = false;
                    this.workers.splice(this.workers.indexOf(this.workers.find(el => el.worker_id == record.worker_id)), 1);
                    this.$emit('notify', 'success', 'Сотрудник ' + record.title + ' убран со смены');
                })
                .catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                });
        },
        changeWorker(newWorker, oldWorker) {
            // console.log(newWorker);
            // console.log(oldWorker);
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
                this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
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
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
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
            // console.log(ev);
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
        sendStop(line, option) {
            axios.post('/api/down_line', 'id=' + line.line_id)
                .then(response => {
                    let f = this.lines.find(el => el.line_id == line.line_id);
                    if (!f.down_from) {
                        axios.post('/api/add_log', {
                            action: 'Остановка линии ' + f.title,
                            extra: 'Причина: ' + this.cancel_reasons[option - 1].title,
                            people_count: f.workers_count,
                            line_id: line.line_id,
                            type: 1,
                            workers: this.workers.filter(el => el.current_line_id == line.line_id).map(el => {
                                return el.worker_id
                            }).join(';')
                        });
                    } else {
                        axios.post('/api/add_log', {
                            action: 'Возобновление работы линии ' + f.title,
                            people_count: f.workers_count,
                            line_id: line.line_id,
                            type: 2,
                            workers: this.workers.filter(el => el.current_line_id == line.line_id).map(el => {
                                return el.worker_id
                            }).join(';')
                        });
                    }
                    if (!f.down_from) {
                        this.lines[this.lines.indexOf(f)].down_from = new Date();
                    } else {
                        this.lines[this.lines.indexOf(f)].down_from = null;
                    }
                    this.$emit('notify', 'success', 'Действие выполнено успешно')
                }).catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                });
        },
        addWorkerFront() {
            this.showList = true;
            this.newWorker = true;
            return;
        },
        addWorker() {
            // this.newWorkerFields.b_start = this.newWorkerFields.break[0].format('hh') 
            axios.post('/api/add_worker',
                this.newWorkerFields
            ).then((response) => {
                if (response.data) {
                    this.newWorker = false;
                    this.workers.push({
                        worker_id: response.data,
                        break_started_at: this.newWorkerFields.break[0].format('HH:mm'),
                        break_ended_at: this.newWorkerFields.break[1].format('HH:mm'),
                        title: this.newWorkerFields.title,
                        company: this.newWorkerFields.title
                    });
                }
            }).catch(err => {
                this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
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
                            el.started_at = el.started_at ? dayjs(el.started_at, 'hh:mm') : dayjs(),
                            el.ended_at  = el.ended_at ? dayjs(el.ended_at, 'HH:mm') : dayjs()
                            el.detector_time = ref([
                                el.detector_start ? dayjs(el.detector_start, 'hh:mm') : dayjs(),
                                el.detector_end ? dayjs(el.detector_end, 'HH:mm') : dayjs()
                            ])
                            //el.time = ref(dayjs(el.started_at, 'hh:mm'));
                            let curTime = new Date();

                            let timeString =
                                (String(curTime.getHours()).length == 1 ? '0' + String(curTime.getHours()) : String(curTime.getHours()))
                                + ':' +
                                (String(curTime.getMinutes()).length == 1 ? '0' + String(curTime.getMinutes()) : String(curTime.getMinutes()))
                                + ':' +
                                (String(curTime.getSeconds()).length == 1 ? '0' + String(curTime.getSeconds()) : String(curTime.getSeconds()));
                            if (timeString > el.ended_at || timeString < el.started_at) {
                                el.done = true;
                            } else {
                                el.done = false;
                            }

                            let arr = [];
                            if (el.master) {
                                let f = this.responsible.find(i => i.responsible_id == el.master);
                                if (f) {
                                    let n = f.title.split(' ');
                                    n = n[0] + ' ' + n[1][0] + '.';
                                    arr.push(n + ', ' + f.position);
                                }
                            } else {
                                delete el.master;
                            }

                            if (el.engineer) {
                                let f = this.responsible.find(i => i.responsible_id == el.engineer);
                                if (f) {
                                    let n = f.title.split(' ');
                                    n = n[0] + ' ' + n[1][0] + '.';
                                    arr.push(n + ', ' + f.position);
                                }
                            } else {
                                delete el.engineer;
                            }

                            el.responsibles = arr.join('\n');
                            return el;
                        })
                        resolve(true);
                    })
                    .catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
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
                    { left: x.scrollWidth, behavior: 'smooth' }
                );
            }, 100);
            return;
        },
        saveLine(record) {
            let fd = new FormData();
            fd.append('line_id', record['line_id']);

            if (record.extra_title) {
                fd.append('extra_title', record['extra_title']);
            }

            fd.append('started_at', record.started_at.format('HH:mm'));
            fd.append('ended_at', record.ended_at.format('HH:mm'));
            if (record.has_detector) {
                fd.append('has_detector', record.has_detector);
                fd.append('detector_start', record.detector_time[0].format('HH:mm'));
                fd.append('detector_end', record.detector_time[1].format('HH:mm'));
            }
            // }
            if (record.workers_count) {
                fd.append('workers_count', record.workers_count);
            }
            fd.append('type_id', record.type_id);
            if (record.color) {
                fd.append('color', record.color);
            }
            fd.append('title', record.title);
            if (record.master != null) {
                fd.append('master', record.master);
            }
            if (record.engineer != null) {
                fd.append('engineer', record.engineer);
            }
            if (record.cancel_reason != null) {
                fd.append('cancel_reason', record.cancel_reason);
                fd.append('cancel_reason_extra', this.cancel_reasons[record.cancel_reason - 1].title);
            }
            if (record.prep_time != null) {
                fd.append('prep_time', record.prep_time);
            }
            if(record.after_time != null) {
                fd.append('after_time', record.after_time);
            }

            axios.post('/api/save_line', fd)
                .then((response) => {
                    this.$emit('notify', 'success', 'Сохранено');
                    let i = this.lines.find(el => el.line_id == record['line_id']);
                    i.started_at = dayjs(record.started_at.format('HH:mm'));
                    i.ended_at = dayjs(record.ended_at.format('HH:mm'));

                    let arr = [];
                    if (i.master) {
                        let f = this.responsible.find(m => m.responsible_id == i.master);
                        if (f) {
                            let n = f.title.split(' ');
                            n = n[0] + ' ' + n[1][0] + '.';
                            arr.push(n + ', ' + f.position);
                        }
                    } else {
                        delete i.master;
                    }

                    if (i.engineer) {
                        let f = this.responsible.find(m => m.responsible_id == i.engineer);
                        if (f) {
                            let n = f.title.split(' ');
                            n = n[0] + ' ' + n[1][0] + '.';
                            arr.push(n + ', ' + f.position);
                        }
                    } else {
                        delete i.engineer;
                    }

                    i.responsibles = arr.join('\n');

                    this.$emit('data-recieved', this.$data);
                })
                .catch((err) => {
                    this.$emit('notify', 'error', 'Что-то пошло не так...');
                })
        },
        changeLine(line_id, worker_id) {
            // Остановить предыдущую смену этого работника (изменить время)
            // Создать новый слот
            let fd = new FormData();
            let worker = this.workers.find(el => el.worker_id == worker_id);
            // console.log(worker);
            fd.append('new_line_id', line_id);
            fd.append('worker_id', worker_id);
            fd.append('old_line_id', worker.current_line_id);
            let oldLine = worker.current_line_id;
            axios.post('/api/change_slot', fd)
                .then(async (response) => {
                    this.lines.find((el) => el.line_id == line_id).count_current += 1;
                    if (oldLine != line_id && oldLine > 0) {
                        this.lines.find((el) => el.line_id == oldLine).count_current -= 1;
                    }
                    this.getSlots();
                    this.processData();
                    this.$emit('data-recieved', this.$data);

                    // this.contKey += 1;
                    this.$forceUpdate();
                    this.listenerSet = false;
                    this.initFunc();
                })
                .catch((err) => {
                    // console.log(err);
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                });
        },
        /*-------------------- LINES END --------------------*/
        initFunc() {
            if (!this.listenerSet) {
                let draggable = this.document.querySelectorAll('.line_items');

                draggable.forEach(line => {
                    line.addEventListener(`dragstart`, (ev) => {
                        ev.target.classList.add(`selected`);
                        let x = this.document.querySelector('.lines-container');
                        setTimeout(() => {
                            x.scrollTo(
                                { top: 0, behavior: 'smooth' }
                            );
                        }, 100);
                        this.document.querySelectorAll('.done-line').forEach(el => {
                            el.classList.toggle('hidden');
                        })
                        this.active = ev.target;
                        // console.log(ev.target)
                    })

                    line.addEventListener(`dragend`, (ev) => {
                        this.document.querySelectorAll('.done-line').forEach(el => {
                            el.classList.toggle('hidden');
                        })
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
        },
        scroll(direction, start) {
            if (start == 1) {
                if (!this.isScrolling) {
                    let cont = this.document.querySelector('.lines-container');
                    cont.scrollTo({
                        left: cont.scrollLeft + (direction ? 280 : -280),
                        behavior: "smooth"
                    });
                    this.isScrolling = setInterval((el) => {
                        cont.scrollTo({
                            left: cont.scrollLeft + (direction ? 280 : -280),
                            behavior: "smooth"
                        });
                        // cont.scrollLeft += (direction ? 280 : -280);
                        // console.log('scroll');
                    }, 300);
                }
            } else if (start == 2) {
                clearInterval(this.isScrolling);
                this.isScrolling = null;
            }
        },
        print_graph() {
            window.open('/api/print_slots', '_blank');
        }
    },
    async created() {
        await this.getResponsible();
        await this.getLines();
        await this.getSlots();
        await this.getWorkers();
        await this.processData();

        this.calcCount();

        this.$emit('data-recieved', this.$data);
    },

    updated() {
        this.initFunc();
    }
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;gap:15px;display: flex;">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список рабочих' : 'Скрыть'
            }}</Button>
        <Button type="default"@click="print_graph">
            <PrinterOutlined />
            Распечатать график</Button>
    </div>
    <div class="lines-container" :key="contKey" ref="linesContainer">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Не задействованы" :headStyle="{ 'background-color': 'white' }">
                <br>
            </Card>
            <section class="line_items">
                <Card v-show="newWorker" draggable="false" class="draggable-card">
                    <span>Имя:</span>
                    <Input v-model:value="newWorkerFields.title" />
                    <br>
                    <span>Компания:</span>
                    <Input v-model:value="newWorkerFields.company" />
                    <br>
                    <span>Обед:</span>
                    <TimePicker v-model:value="newWorkerFields.break[0]" format="HH:mm" :showTime="true"
                        :allowClear="true" type="time" :showDate="false" style="width:fit-content;" />
                    <TimePicker v-model:value="newWorkerFields.break[1]" format="HH:mm" :showTime="true"
                        :allowClear="true" type="time" :showDate="false" style="width:fit-content;" />
                    <Button type="primary" style="width:100%" @click="addWorker">
                        Сохранить
                    </Button>
                </Card>
                <Card :title="v.title" draggable="true" class="draggable-card"
                    v-for="(v, k) in workers.filter(el => el.current_line_id == null)"
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
                </Card>
            </section>
        </div>
        <div class="line" v-for="line in lines" :data-id="line.line_id" :class="(line.done || !line.has_plans) ? 'done-line' : ''">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <template #title>
                    <div class="line_title" :data-id="line.line_id" v-show="!line.edit">
                        <b style="font-weight:700;">{{ line.title }}</b>
                        <br>
                        <span style="font-weight:400;display: block;width: 100%;white-space: collapse;">{{ line.extra_title }}</span>
                    </div>
                    <Input v-show="line.edit" :data-id="line.line_id" class="line_title" v-model:value="line.title"
                        style="display: block;color:black;" />
                    <Input v-show="line.edit" class="line_title" v-model:value="line.extra_title"
                        style="display: block;color:black;" placeholder="Производительность"/>

                    <div style="display: flex;justify-content: space-between;align-items: center;">
                        <Switch v-model:checked="line.edit" checked-children="Редактирование"
                            un-checked-children="Просмотр" class="title-switch"
                            @change="(c, e) => { !c ? saveLine(line) : '' }" />
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
                                :title="'Время работы было изменено по причине: ' + cancel_reasons.find((el) => el.value == line.cancel_reason).title">
                                <InfoCircleOutlined style="color:#f48c05;font-size:24px;margin-right:12px;" />
                            </Tooltip>
                            <Tooltip :title="line.down_from ? 'Возобновить работу' : 'Остановить работу'">
                                <ForwardOutlined @click="sendStop(line)" v-if="line.down_from"
                                    style="height:min-content;color:#82ff82;font-size:22px;" />
                                <Popconfirm v-else :showCancel="false" id="popover" placement="right">
                                    <template #title>
                                        <Select placeholder="Причина остановки" style="margin-top: 10px; width: 100%;"
                                            @change="(value, option) => sendStop(line, value)">
                                            <SelectOption v-for="i in cancel_reasons" v-model:value="i.value">
                                                <Tooltip :title="i.title">
                                                    {{ i.title }}
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
                        <!-- <span style="display: flex; justify-content: space-between; align-items: center;"
                            v-if="line.line_id != -1">
                            <span>Работает с:</span>
                            <TimePicker v-model:value="line.time" format="HH:mm" :showTime="true" :allowClear="true"
                                type="time" :showDate="false" style="width:fit-content;" />
                        </span> -->
                        <span>Время работы:</span><br />
                        <TimePicker v-model:value="line.started_at" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false" style="width:fit-content;" />
                        <TimePicker v-model:value="line.ended_at" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false" style="width:fit-content;" />
                        <Select v-model:value="line.cancel_reason" placeholder="Причина переноса старта"
                            style="margin-top: 10px; width: 100%;">
                            <SelectOption v-for="i in cancel_reasons" v-model:value="i.value">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                        <span>Подготовительное время(мин):</span><Input v-model:value="line.prep_time" placeholder="0"/>
                        <span>Заключительное время(мин):</span><Input v-model:value="line.after_time" placeholder="0"/>
                        <br>
                        <br>
                        <RadioGroup v-model:value="line.type_id">
                            <RadioButton value="1">Варка</RadioButton>
                            <RadioButton value="2">Упаковка</RadioButton>
                        </RadioGroup>
                        <span>Ответственные:</span>
                        <Select v-model:value="line.master" style="width:100%;">
                            <SelectOption v-for="i in responsible" v-model:value="i.responsible_id">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                        <Select v-model:value="line.engineer" style="width:100%;margin-top:10px;">
                            <SelectOption v-for="i in responsible" v-model:value="i.responsible_id">
                                {{ i.title }}
                            </SelectOption>
                        </Select>
                        <Checkbox v-model:checked="line.has_detector" style="margin-top:10px;">
                            Установить металодетектор
                        </Checkbox>
                        <div v-if="line.has_detector">
                            <TimeRangePicker v-model:value="line.detector_time" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false" style="width:fit-content;" />
                        </div>    
                    </div>
                </template>
                <template v-else>
                    <div class="line_sub-title">
                        <span :style="line.count_current < line.workers_count ? 'color:#ff4d4f;' : ''">Необходимо
                            работников: {{ line.workers_count ? line.workers_count : 'без ограничений'
                            }}</span>
                        <br>
                        <span>Всего работников на линии: {{ line.count_current ? line.count_current : '0' }}</span>
                        <br>
                        <span v-show="line.responsibles">Ответственные: <br />{{ line.responsibles }}</span>
                    </div>
                </template>
            </Card>
            <section class="line_items">
                <Card :title="v.title" draggable="true" class="draggable-card"
                    v-for="(v, k) in workers.filter(el => el.current_line_id == line.line_id)" :key="v.worker_id"
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
                        <span v-if="v.break_started_at && v.break_ended_at" style="height: fit-content;">
                            Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
                        </span>
                        <div style="display: flex; gap:5px;">
                            <Tooltip title="Убрать со смены">
                                <Popconfirm title="Укажите причину" ok-text="Смена работника закончена досрочно"
                                    cancel-text="Работник не вышел на смену" @confirm="deleteWorker(v, false)"
                                    @cancel="deleteWorker(v, true)">
                                    <UserDeleteOutlined
                                        style="color:#ff4d4f;padding:5px;border: 2px solid #ff4d4f;font-size:25px;border-radius:20px;" />
                                </Popconfirm>
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
        <FloatButton type="default" @click="addWorkerFront">
            <template #tooltip>
                <div>Добавить сотрудника</div>
            </template>
            <template #icon>
                <UserAddOutlined />
            </template>
        </FloatButton>
        <BackTop />
    </FloatButtonGroup>
    <FloatButton @dragover="scroll(true, 1)" @dragleave="scroll(true, 2)" @mouseover="scroll(true, 1)"
        @mouseleave="scroll(true, 2)" style="top:50%;">
        <template #icon>
            <RightOutlined />
        </template>
    </FloatButton>
    <FloatButton @dragover="scroll(false, 1)" @dragleave="scroll(false, 2)" @mouseover="scroll(false, 1)"
        @mouseleave="scroll(false, 2)" style="top:50%;left:1%">
        <template #icon>
            <LeftOutlined />
        </template>
    </FloatButton>
</template>