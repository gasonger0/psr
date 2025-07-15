<script setup>
import { Button, Modal, Table } from 'ant-design-vue';
import axios from 'axios';
import { ref } from 'vue';
import * as dayjs from 'dayjs';
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
            window.open('/api/load_logs', '_blank');
        }
    },
    updated() {
        axios.get('/api/get_logs')
            .then((response) => {
                if (response.data) {
                    this.logs = response.data.map(el => {
                        el.created_at = dayjs.default(el.created_at).subtract(3, 'hour').format('HH:mm:ss');
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