<script setup>
import { BackTop, Card, FloatButton, Input, Switch, TimeRangePicker, Button } from 'ant-design-vue';
import { ref } from 'vue';
import axios from 'axios';
import Loading from './loading.vue';
import dayjs from 'dayjs';
import { PlusCircleOutlined } from '@ant-design/icons-vue';
</script>
<script>
export default {
    data() {
        return {
            lines: [
                {
                    id: 1,
                    title: 'Линия №1',
                    started_at: new Date(),
                    updated_at: new Date()
                },
                {
                    id: 2,
                    title: 'Линия №2',
                    started_at: new Date(),
                    updated_at: new Date()
                },
                {
                    id: 3,
                    title: 'Линия №3',
                    started_at: new Date(),
                    updated_at: new Date()
                },
            ],
            workers: [
                {
                    id: 1,
                    name: 'Комаров И.Н.',
                    company: 'ООО',
                    break_start: '12:00',
                    break_end: '12:30'
                },
                {
                    id: 3,
                    name: 'Пупупу',
                    company: 'Треш',
                }
            ],
            products: [
                {
                    id: 1,
                    title: 'Зефир',
                    line_id: 1,
                    started_at: new Date(),
                    ended_at: new Date(),
                    people_count: 2
                }
            ],
            slots: [
                {
                    id: 1,
                    worker_id: 1,
                    line_id: 1,
                    started_at: '8:00',
                    ended_at: '10:00'
                },
                {
                    id: 2,
                    worker_id: 1,
                    line_id: 2,
                    started_at: '10:00',
                    ended_at: '17:00'
                }
            ],
            isLoading: ref(true),
            document: document,
            listenerSet: false
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
                        this.isLoading = false;
                        resolve(true);
                    });
            })
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
        changeLine(line_id, worker_id) {

            // Остановить предыдущую смену этого работника (изменить время)
            // Создать новый слот
            // axios.post('/api/add_slot') {
            //     JSON.stringify({

            //     })
            // }
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
        saveLine() {

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
                })

                line.addEventListener(`dragend`, (ev) => {
                    console.log(ev);
                    if (ev.target.classList.contains('selected')) {
                        // console.log(this.workers.find(el => el.worker_id == ev.target.dataset.id));
                        // let worker = this.workers.find(el => el.worker_id == ev.target.dataset.id);
                        // if (worker) {
                        //     worker.current_line_id = Number(ev.target.closest('.line').dataset.id);
                        // }
                        ev.target.classList.remove(`selected`);
                        this.changeLine(ev.target.closest('.line').dataset.id, ev.target.dataset.id);
                        // this.getWorkers();
                        this.$emit('data-recieved', this.$data);
                    }
                });

                line.addEventListener('dragover', (ev) => {
                    ev.preventDefault();
                    const activeElement = document.querySelector('.selected');
                    const currentElement = ev.target;
                    // ev.target.classList.contains('draggable-card') ? ev.target :
                    // ev.target.closest('.draggable-card');
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
        this.document.querySelector('.lines-container').scrollTo({left: 0});
    }
}
</script>
<template>
    <div class="lines-container">
        <div class="line" v-for="line in lines" :data-id="line.line_id">
            <Card :bordered="false" class="head">
                <template #title>
                    <div class="line_title" :data-id="line.line_id">
                        <b>{{ line.title }}</b>
                    </div>
                    <Switch v-model:checked="line.edit" checked-children="Редактирование" un-checked-children="Просмотр"
                        class="title-switch" @change="(c, e) => {c ? saveLine(line) : return;}"/>
                </template>
                <template v-if="line.edit">
                    <div style="width:100%; max-width:400px;">
                        <span
                            style="display: flex; justify-content: space-between; margin-bottom:10px;align-items: center;">
                            <span style="height:fit-content;">Необходимо:&nbsp;&nbsp;</span>
                            <Input v-model:value="line.workers_count" />
                        </span>
                        <TimeRangePicker v-model:value="line.time" format="HH:mm" :showTime="true" :allowClear="true"
                            type="time" :showDate="false"
                            style="display: flex; justify-content: space-between; align-items: center;" />
                    </div>
                </template>
                <template v-else>
                    <div class="line_sub-title">
                        <span :style="line.count_current < line.workers_count ? 'color:red;' : ''">Необходимо
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
                    :style="v.on_break ? 'opacity: 0.6' : ''" :data-id="v.worker_id" @mouseover="el.showDelete = true"
                    @mouseleave="el.showDelete = false">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
                    <span v-show="v.break_started_at && v.break_ended_at">
                        Перерыв на обед: {{ v.break_started_at + ' - ' + v.break_ended_at }}
                    </span>
                    <Button type="primary" danger ghost v-show="showDelete">Убрать со смены</Button>
                </Card>
            </section>
        </div>
    </div>
    <Loading :open="isLoading" />
    <FloatButton type="primary" @click="addLineFront">
        <template #tooltip>
            <div>Добавить линию</div>
        </template>
        <template #icon>
            <PlusCircleOutlined />
        </template>
    </FloatButton>
    <BackTop />
</template>