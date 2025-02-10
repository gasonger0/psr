<script setup>
import { ref } from 'vue';
import { Modal, Input, Table, Checkbox } from 'ant-design-vue';
import axios from 'axios';
</script>
<script>
export default {
    props: {
        data: {
            type: Object
        },
        open: {
            type: Boolean
        }
    },
    data() {
        return {
            workers: ref([]),
        }
    },
    methods: {
        close(send) {
            if (send) {
                // console.log(this.workers);
                this.workers = this.workers.filter(el => el.check).map(el => {
                    return {
                        worker_id: el.worker_id,
                        ktu: el.ktu
                    }
                });
                // console.log(this.workers);
                axios.post('/api/get_xlsx', this.workers)
                    .then(response => {
                        if (response.data) {
                            let url = response.data;
                            // console.log(response);
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
                        // console.log(err);
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                    });
                this.$emit('close-modal');
            } else {
                this.$emit('close-modal');
            }
        }
    },
    updated() {
        if (this.workers.length == 0) {
            this.workers = this.$props.data.workers.map(el => {
                el.ktu = 1;
                el.check = true;
                return el;
            })
        }
        // console.log(this.workers);
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" cancelText="Закрыть" okText="Сформировать отчёт"
        style="min-width:20vw; min-height: 30vh;" @ok="close(true)" @cancel="close(false)" title="Сформировать отчёт">
        <div class="table-container">
            <Table :dataSource="$props.data.workers" :columns="[{
                dataIndex: 'check',
                title: ''
            }, {
                dataIndex: 'title',
                title: 'Работник'
            }, {
                dataIndex: 'ktu',
                title: 'КТУ'
            }]">
                <template #bodyCell="{ record, column, text }">
                    <template v-if="column.dataIndex == 'ktu'">
                        <Input v-model:value="record.ktu" />
                    </template>
                    <template v-if="column.dataIndex == 'check'">
                        <Checkbox v-model:checked="record[column.dataIndex]" />
                    </template>
                </template>
            </Table>
        </div>
        <!-- <Select v-model:value="worker" style="width:100%;" type="number" label="Работник">
            <SelectOption v-for="(v, k) in $props.data.workers" v-model:value="v.worker_id" :label="v.title">{{ v.title }}</SelectOption>
        </Select> -->
    </Modal>
</template>
