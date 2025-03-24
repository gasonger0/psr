<script setup>
import { Table, Modal } from 'ant-design-vue';
import { DeleteOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
import Loading from './loading.vue';
import { ref } from 'vue';
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
            columns: [{
                title: '',
                dataIndex: 'delete'
            }, {
                title: 'Дата',
                dataIndex: 'date'
            },{
                title: 'Смена',
                dataIndex: 'isDay'
            }, {
                title: 'Анализ заказов',
                dataIndex: 'order'
            }, {
                title: 'План продукции',
                dataIndex: 'plan'
            }, {
                title: 'График рабочих',
                dataIndex: 'workers'
            }],
            plans: ref([]),
            isLoading: ref(false)
        }
    },
    methods: {
        getPlans() {
            this.isLoading = true;
            axios.get('/api/get_plans')
                .then((response) => {
                    this.plans = response.data;
                    this.isLoading = false;
                });
        },
        clear(date) {
            axios.post('/api/clear_plan', { date: date })
                .then(response => {
                    this.plans.splice(this.plans.indexOf(this.plans.find(el => el.date == date)), 1);
                })
        },
        close() {
            this.$emit('close-modal');
        }
    },
    created() {
        this.getPlans(); 
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" closable="true"
        style="min-width:20vw; min-height: 30vh;" @cancel="close" :footer="null">
        <div class="table-container">
            <Table :columns="columns" :dataSource="plans">
                <template #bodyCell="{ column, record, text }">
                    <template v-if="column.dataIndex == 'delete'">
                        <DeleteOutlined @click="clear(record.date)" />
                    </template>
                    <template v-if="column.dataIndex == 'isDay'">
                        <template v-if="record[column.dataIndex]">
                            День
                        </template>
                        <template v-else>
                            Ночь
                        </template>
                    </template>
                    <template v-if="column.dataIndex != 'date' && column.dataIndex != 'delete'">
                        <template v-if="record[column.dataIndex]">
                            <span>Да</span>
                        </template>
                        <template v-else>
                            <span>Нет</span>
                        </template>
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
    <Loading :open="isLoading" />
</template>