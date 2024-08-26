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
            isLoading: ref(true)
        }
    },
    methods: {

        // FILE UPLOAD //
        processXlsx(file) {
            return false;
        },
        // FILE UPLOAD //

        getLines() {
            this.isLoading = true;
            axios.get('/api/get_lines')
                .then(response => {
                    this.lines = response.data;
                    this.isLoading = false;
                })
        },
        getWorkers() {
            this.isLoading = true;
            axios.get('/api/get_workers')
                .then(response => {
                    this.workers = response.data;
                    this.slots.forEach(slot => {
                        console.log(slot);
                        // if (new Date(slot.started_at) < new Date() && new Date() < new Date(slot.ended_at)) {
                        //     let worker = this.workers.find(worker => worker.worker_id == slot.workers_id);
                        //     console.log(worker);
                        //     worker.current_line_id = slot.line_id;
                        // }
                    })
                    this.isLoading = false;
                });
        },
        getSlots() {
            this.isLoading = true;
            axios.get('/api/get_slots')
                .then(response => {
                    this.slots = response.data;
                    this.isLoading = false;
                });
        }
    },
    created() {
        this.getLines();
        this.getSlots();
        this.getWorkers();
    }
}
</script>
<template>
    <div class="top-container">
        <Upload
            :v-model:file-list="uploadedFile"
            name="file"
            :before-upload="processXlsx">
            <Button type="primary" style="background-color: green;">
                <UploadOutlined/>
                Новый график
            </Button>
        </Upload>
        <Button type="default">
            <BarChartOutlined />
            Отчёт
        </Button>
        <Button type="primary">
            <EditOutlined/>
            Редактировать график
        </Button>
    </div>
    <div class="lines-container">
        <div class="line" v-for="line in lines">
            <div class="line_title" :data-id="line.line_id">
                <b>{{ line.title }}</b>
            </div>
            <div class="line_sub-title">

            </div>

            <section class="line_items" v-for="(v, k) in workers.filter(el => el.current_line_id == line.line_id)">
                <Card :title="v.name">
                    <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template>
                    <span  v-show="v.break_start && v.break_end">
                        Перерыв на обед: {{ v.break_start + ' - ' + v.break_end }} 
                    </span>
                </Card>
            </section>
        </div>
    </div>
    <Loading :open="isLoading"/>
</template>