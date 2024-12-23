<script setup>
import { Button, Modal, Table } from 'ant-design-vue';
import axios from 'axios';
import { ref } from 'vue';
import dayjs from 'dayjs';
</script>
<script>
export default {
    props: {
        open: {
            type: Boolean
        }
    },
    data() {
        return {
            logs: ref([]),
            columns: [{
                title: 'Дата и время',
                dataIndex: 'created_at'
            }, {
                title: 'Действие',
                dataIndex: 'action'
            }, {
                title: 'Дополнительно',
                dataIndex: 'extra'
            }, {
                title: 'Человек на линии',
                dataIndex: 'people_count'
            }, {
                title: 'Линия',
                dataIndex: 'line'
            }]
        }
    },
    methods: {
        close() {
            this.$emit('close-modal');
        },
        loadLog() {
            axios.get('/api/load_logs')
                .then(response => {
                    if (response.data) {
                        let url = response.data;
                        console.log(response);
                        let a = document.createElement('a');
                        if (typeof a.download === undefined) {
                            window.location = url;
                        } else {
                            a.href = url;
                            a.download = response.data;
                            document.body.appendChild(a);
                            a.click();
                        }
                    }
                })
                .catch((err) => {
                    console.log(err);
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                });
        }
    },
    updated() {
        axios.get('/api/get_logs')
            .then((response) => {
                if (response.data) {
                    this.logs = response.data.map(el => {
                        el.created_at = dayjs(el.created_at).format('DD.MM.YYYY HH:mm:ss');
                        return el;
                    });
                }
            })
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" @cancel="close(false)" :closable="true" style="width:50vw;" :footer="false">
        <Button type="primary" @click="loadLog">Выгрузить в xlsx</Button>
        <div class="table-container">
            <!-- <div style="display: flex; gap: 10px;margin-bottom:10px;">
                    <Button>Простои</Button>
                    <Button>Отчёты</Button>
                    <Button>Загрузка остатков</Button>
                </div> -->

            <Table :columns="columns" :dataSource="logs"></Table>
        </div>
    </Modal>
</template>