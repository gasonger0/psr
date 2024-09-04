<script setup>
import { Table, Switch, Modal, TimeRangePicker } from 'ant-design-vue';
import { ref } from 'vue';
import dayjs from 'dayjs';
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
            slots: null,
            lines: null,
            workers: null,
            edit: ref(false)
        }
    },
    methods: {
        processRows() {
            this.workers.forEach(el => {
                el.break = {
                    time: ref([dayjs(el.break_started_at, 'hh:mm:ss'), dayjs(el.break_ended_at, 'HH:mm:ss')])
                };

                let slots = this.slots.filter((i) => {
                    if (i.worker_id == el.worker_id) {
                        return i;
                    }
                });

                if (slots) {
                    slots.forEach(n => {
                        el[n.line_id] = {
                            time: ref([dayjs(n.started_at, 'hh:mm:ss'), dayjs(n.ended_at, 'HH:mm:ss')]),
                            slot_id: n.slot_id,
                            line_id: n.line_id
                        }
                    });
                }
            });
            console.log(this.workers);
        },
        processColumns() {
            this.lines.forEach(el => {
                el.dataIndex = el.line_id;
                el.width = 200;
            });

            this.lines.unshift({
                title: 'Сотрудник',
                dataIndex: 'title',
                width: 200,
                fixed: 'left'
            }, {
                title: 'Время обеда',
                dataIndex: 'break',
                width: 200
            });
        },
        updateData(upd) {
            if (!upd) {
                let slots = [];
                let workers = this.workers.map(el => {
                    if (el) {
                        for (let i in el) {
                            if (/^\d+$/.test(i)) {
                                slots.push({
                                    slot_id: el[i].slot_id,
                                    line_id: el[i].line_id,
                                    started_at: el[i].time[0].format('HH:mm:ss'),
                                    endded_at: el[i].time[1].format('HH:mm:ss'),
                                })
                            }
                        }
                        el.break_started_at = el.break.time[0].format('HH:mm:ss');
                        el.break_ended_at = el.break.time[1].format('HH:mm:ss');
                        // el.break = undefined;
                        return el;
                    }
                });
                this.$emit('close-modal', [slots, workers]);
            } else {
                this.$emit('close-modal');
            }
            return;
            //     slots:   slots,
            //     workers: workers
            // }
        }
    },
    updated() {
        if (this.$props.data && !this.slots) {
            this.slots = this.$props.data.slots.slice();
            this.lines = this.$props.data.lines.slice();
            this.workers = this.$props.data.workers.slice();

            this.processRows();
            this.processColumns();
        }
    },
    mounted() {
        console.log(this.$props.data);
        if (this.$props.data) {
            this.slots = this.$props.data.slots.slice();
            this.lines = this.$props.data.lines.slice();
            this.workers = this.$props.data.workers.slice();

            this.processRows();
            this.processColumns();
        }
    },
}
</script>
<template>
    <Modal v-model:open="$props.open" cancelText="Закрыть" title="Редактировать график" 
        @ok="updateData" @cancel="updateData(false)" style="width:98vw;">
        <Switch checked-children="Редактирование" un-checked-children="Просмотр" v-model:checked="edit" />
        <br/>
        <br/>
        <div class="table-container">
            <Table :dataSource="workers" :columns="lines" :pagination="{pageSize: 6}" small>
                <template #bodyCell="{ column, record, text }">
                    <template v-if="column.dataIndex != 'title' &&
                        record[column.dataIndex]">
                        <template v-if="!edit">
                            {{ record[column.dataIndex]['time'][0].format('HH:mm') }} - {{
                                record[column.dataIndex]['time'][1].format('HH:mm') }}
                        </template>
                        <template v-else>
                            <TimeRangePicker v-model:value="record[column.dataIndex]['time']"
                                @change="(ev) => { console.log(ev); }" format="HH:mm" :showTime="true"
                                :allowClear="true" type="time" :showDate="false" :size="'small'"/>
                        </template>
                    </template>
                    <template v-else>
                        {{ record[column.dataIndex] }}
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
</template>