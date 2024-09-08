<script setup>
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, Button, FloatButtonGroup, Tooltip } from 'ant-design-vue';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Loading from './loading.vue';
import dayjs from 'dayjs';
import { LoginOutlined, PlusCircleOutlined, UserAddOutlined, UserDeleteOutlined } from '@ant-design/icons-vue';
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
            active: null
        }
    },
    methods: {
        async getWorkers() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_workers')
                    .then(response => {
                        this.workers = response.data;
                        let curTime = new Date();
                        let timeString = curTime.getHours() + ':' + curTime.getMinutes() + ':' + curTime.getSeconds();
                        this.slots.forEach(slot => {
                            if (slot.started_at < timeString && timeString < slot.ended_at) {
                                let worker = this.workers.find(worker => worker.worker_id == slot.worker_id);
                                worker.current_line_id = slot.line_id;
                                worker.current_slot = slot;
                            }
                        });
                        this.workers.forEach((worker) => {
                            if (worker.break_started_at < timeString < worker.break_ended_at) {
                                worker.on_break = true;
                            }
                        })

                        console.log(this.workers);
                        this.isLoading = false;
                        resolve(true);
                    });
            })
        },
        deleteWorker(record) {
            this.isLoading = true;
            axios.post('/api/delete_slot', {worker_id: record.worker_id})
                .then(response => {
                    this.isLoading = false;
                    this.workers.splice(this.workers.indexOf(this.workers.find(worker)), 1);
                    this.$emit('notify', 'success', 'Сотрудник ' + record.title + ' убран со смены');
                });
        },
        saveWorker(record) {

        },
        async getSlots() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_slots')
                    .then(response => {
                        this.slots = response.data;
                        resolve(true);
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

        /*------------------- LINES START -------------------*/
        async getLines() {
            return new Promise((resolve, reject) => {
                this.isLoading = true;
                axios.get('/api/get_lines')
                    .then(response => {
                        this.lines = response.data;
                        this.lines.map((el) => {
                            el.edit = false;
                            el.showDelete = ref(false);
                            el.time = ref([dayjs(el.started_at, 'hh:mm:ss'), dayjs(el.ended_at, 'HH:mm:ss')]);
                            return el;
                        })
                        resolve(true);
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
            if (record['line_id'] != -1) {
                fd.append('line_id', record['line_id']);
            }
            fd.append('workers_count', record.workers_count);
            fd.append('started_at', record.time[0].format('HH:mm:ss'));
            fd.append('ended_at', record.time[1].format('HH:mm:ss'))
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
            axios.post('/api/change_slot', fd).then((response) => {
                this.lines.find((el) => el.line_id == line_id).count_current += 1;
                // worker.current_line_id = Number(line_id);
                // this.calcCount();
            });
        }
        /*-------------------- LINES END --------------------*/
    },
    async created() {
        await this.getLines();
        await this.getSlots();
        await this.getWorkers();

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
    <div class="lines-container">
        <div class="line" v-for="line in lines" :data-id="line.line_id">
            <Card :bordered="false" class="head">
                <template #title>
                    <!-- <Input v-show="line.edit && line.line_id == -1" :data-id="line.line_id" class="line_title" v-model:value="line.title" /> -->
                    <div class="line_title" :data-id="line.line_id" v-show="!line.edit && line.line_id != -1">
                        <b>{{ line.title }}</b>
                    </div>
                    <Switch v-model:checked="line.edit" checked-children="Редактирование" un-checked-children="Просмотр"
                        class="title-switch" @change="(c, e) => { !c ? saveLine(line) : '' }" />
                </template>
                <template v-if="line.edit">
                    <div style="width:100%; max-width:400px;">
                        <span
                            style="display: flex; justify-content: space-between; margin-bottom:10px;align-items: center;">
                            <span style="height:fit-content;">Необходимо:&nbsp;&nbsp;</span>
                            <Input v-model:value="line.workers_count" type="number" />
                        </span>
                        <TimeRangePicker v-model:value="line.time" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false"
                            style="display: flex; justify-content: space-between; align-items: center;" />
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
                        <span v-show="v.break_started_at && v.break_ended_at" style="height: fit-content;">
                            Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
                        </span>
                        <Tooltip title="Убрать со смены">
                            <UserDeleteOutlined style="color:#ff4d4f;padding:5px;border: 2px solid #ff4d4f;font-size:15px;border-radius:20px;" @click="deleteWorker(v)"/>
                        </Tooltip>
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
        <FloatButton type="default" @click="addLineFront">
            <template #tooltip>
                <div>Добавить сотрудника</div>
            </template>
            <template #icon>
                <UserAddOutlined />
            </template>
        </FloatButton>
        <BackTop />
    </FloatButtonGroup>
</template>