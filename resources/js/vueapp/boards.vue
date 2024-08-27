<script setup>
import { Card, Button, Upload } from 'ant-design-vue';
import { ref } from 'vue';
import axios from 'axios';
import Loading from './loading.vue';
import { UploadOutlined, BarChartOutlined, EditOutlined } from '@ant-design/icons-vue';
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
            uploadedFile: ref(null),
            isLoading: ref(true),
            document: document
        }
    },
    methods: {

        // FILE UPLOAD //
        processXlsx(file) {
            return false;
        },
        // FILE UPLOAD //

        async getLines() {
            return new Promise((resolve, reject) => {
                this.isLoading = true;
                axios.get('/api/get_lines')
                    .then(response => {
                        this.lines = response.data;
                        resolve(true);
                    });
            });
        },
        async getWorkers() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_workers')
                    .then(response => {
                        this.workers = response.data;
                        let curTime = new Date();
                        let timeString = curTime.getHours() + ':' + curTime.getMinutes() + ':' + curTime.getSeconds();
                        this.slots.forEach(slot => {
                            console.log(slot);
                            if (slot.started_at < timeString && timeString < slot.ended_at) {
                                let worker = this.workers.find(worker => worker.worker_id == slot.workers_id);
                                console.log(worker);
                                worker.current_line_id = slot.line_id;
                            }
                        });
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
        }
    },
    async created() {
        await this.getLines();
        await this.getSlots();
        await this.getWorkers();

        this.lines = this.lines.map((line) => {
            line.count_current = this.workers.filter((wrkr) => wrkr.current_line_id == line.line_id).length;
            return line;
        })
    },
    updated() {
        let draggable = this.document.querySelectorAll('.line');

        draggable.forEach((line) => {
            line.addEventListener(`dragstart`, (evt) => {
                evt.target.classList.add(`selected`);
            })

            line.addEventListener(`dragend`, (evt) => {
                evt.target.classList.remove(`selected`);
            });

            line.addEventListener(`dragover`, (ev) => {
                ev.preventDefault();

                const activeElement = document.querySelector(`.selected`);
                const currentElement = ev.target;
                
                console.log(activeElement);
                console.log(currentElement);
                currentElement.removeChild(activeElement);
                activeElement.closest('line').appendChild(activeElement);
            });
        })
    }
}
</script>
<template>
    <div class="top-container">
        <Upload :v-model:file-list="uploadedFile" name="file" :before-upload="processXlsx">
            <Button type="primary" style="background-color: green;">
                <UploadOutlined />
                Новый график
            </Button>
        </Upload>
        <Button type="default">
            <BarChartOutlined />
            Отчёт
        </Button>
        <Button type="primary">
            <EditOutlined />
            Редактировать график
        </Button>
    </div>
    <div class="lines-container">
        <div class="line" v-for="line in lines">
            <Card :bordered="false">
                <template #title>
                    <div class="line_title" :data-id="line.line_id">
                        <b>{{ line.title }}</b>
                    </div>
                </template>
                <div class="line_sub-title">
                    <span>Необходимо работников: {{ line.workers_count ? line.workers_count : 'без ограничений'
                        }}</span>
                    <br>
                    <span>Всего работников на линии: {{ line.count_current }}</span>
                </div>
            </Card>
            <section class="line_items" v-for="(v, k) in workers.filter(el => el.current_line_id == line.line_id)">
                <Card :title="v.title" draggable="true" class="draggable-card">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
                    <span v-show="v.break_started_at && v.break_ended_at">
                        Перерыв на обед: {{ v.break_started_at + ' - ' + v.break_ended_at }}
                    </span>
                </Card>
            </section>
        </div>
    </div>
    <Loading :open="isLoading" />
</template>