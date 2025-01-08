<script setup>
import { DeleteOutlined } from '@ant-design/icons-vue';
import { Modal, Table, TabPane, Tabs, Button, Input, Select, TableSummary, TableSummaryRow, TableSummaryCell, Pagination } from 'ant-design-vue';
import axios from 'axios';
import { reactive, ref, watch } from 'vue';

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
            workers: reactive([]),
            responsibles: reactive([]),
            activeTab: ref('1'),
            tabs: {
                1: "Работники",
                2: "Ответственные"
            },
            columns: [[{
                title: '',
                dataIndex: 'delete',
                width: '5%'
            },{
                title: 'ФИО',
                dataIndex: 'title',
                width: '60%'
            }, {
                title: 'Компания',
                dataIndex: 'company',
            }], [{
                title: '',
                dataIndex: 'delete',
                width: '5%'
            },{
                title: 'ФИО',
                dataIndex: 'title',
                width: '40%'
            }, {
                title: 'Доложность',
                dataIndex: 'position',
            }]],
            positions: [{
                value: 0,
                label: 'Рабочий'
            }, {
                value: 1,
                label: 'Начальник смены'
            }, {
                value: 2,
                label: 'Мастер смены'
            }, {
                value: 3,
                label: 'Мастер варочного участка'
            }, {
                value: 4,
                label: 'Инженер'
            }, {
                value: 5,
                label: 'Наладчик'
            }],
            currentPage: {
                1: ref(1),
                2: ref(1)
            }
        }
    },
    methods: {
        getWorkers() {
            axios.get('/api/get_workers')
                .then((response) => {
                    this.workers = response.data;
                });
        },
        getResponsibles() {
            axios.get('/api/get_responsible')
                .then((response) => {
                    this.responsibles = response.data;
                });
        },
        addNew(k) {
            if (k == 1) {
                this.workers.push({
                    title: '',
                    company: ''
                });
            } else {
                this.responsibles.push({
                    title: '',
                    position: ''
                });
            }
        },
        exit() {
            axios.post('/api/edit_workers', this.workers);
            axios.post('/api/edit_responsible', this.responsibles);
            this.$emit('close-modal');
        }
    },
    updated() {
        this.getWorkers();
        this.getResponsibles();
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" @close="$emit('close-modal')" :closable="false" style="width:40%;height:80%;">
        <div style="max-height:74vh;overflow:scroll">
            <Tabs v-model:activeKey="activeTab">
                <TabPane v-for="(v, k) in tabs" :key="k" :tab="v">
                    <Table :dataSource="k == 1 ? workers : responsibles" :columns="k == 1 ? columns[0] : columns[1]"
                        :pagination="false">
                        <template #bodyCell="{ column, record, text }">
                            <template v-if="column.dataIndex == 'position'">
                                <Select v-model:value="record[column.dataIndex]" style="width: 100%;"
                                    :options="positions">
                                </Select>
                            </template>
                            <template v-else-if="column.dataIndex == 'delete'">
                                <DeleteOutlined @click="k == 1 ? workers.splice(workers.indexOf(record), 1) : responsibles.splice(responsibles.indexOf(record), 1)"/>
                            </template>
                            <template v-else>
                                <Input v-model:value="record[column.dataIndex]" />
                            </template>
                        </template>
                        <template #summary>
                            <TableSummary>
                                <TableSummaryRow>
                                    <TableSummaryCell :col-span="4" style="padding:0;">
                                        <Button type="primary" @click="addNew(k)"
                                            style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                                    </TableSummaryCell>
                                </TableSummaryRow>
                            </TableSummary>
                        </template>
                    </Table>
                </TabPane>
            </Tabs>
        </div>
        <template #footer>
            <Button type="default" @click="exit()">Закрыть</Button>
        </template>
    </Modal>
</template>
