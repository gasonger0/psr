<script setup>
import { Table, Switch, Modal, TimePicker, Button } from 'ant-design-vue';
import { ref } from 'vue';
import dayjs from 'dayjs';
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
            slots: null,
            lines: null,
            workers: null,
            edit: ref(false),
            updSlots: [],
            updWorkers: []
        }
    },
    methods: {
        processRows() {
            this.workers.forEach(el => {
                el.break = {
                    time: ref([
                        el.break_started_at != null ? dayjs.default(el.break_started_at, 'hh:mm') : dayjs.default(), 
                        el.break_ended_at != null ? dayjs.default(el.break_ended_at, 'hh:mm') : dayjs.default()
                    ]),
                    worker_id: el.worker_id
                };
                if (sessionStorage.getItem("isDay") == "false" && el.break.time[0] > el.break.time[1]){
                    el.break.time[1] = el.break.time[1].add(1, 'day');
                }

                let slots = this.slots.filter((i) => {
                    if (i.worker_id == el.worker_id) {
                        return i;
                    }
                });

                if (slots) {
                    slots.forEach(n => {
                        el[n.line_id] = {
                            time: ref([dayjs.default(n.started_at, 'hh:mm'), dayjs.default(n.ended_at, 'HH:mm')]),
                            slot_id: n.slot_id,
                            line_id: n.line_id
                        }
                    });
                }
            });
            // console.log(this.workers);
        },
        processColumns() {
            this.lines.forEach(el => {
                el.dataIndex = el.line_id;
                el.width = 150;
            });

            this.lines.unshift({
                title: 'Сотрудник',
                dataIndex: 'title',
                width: 120,
                fixed: 'left'
            }, {
                title: 'Время обеда',
                dataIndex: 'break',
                width: 120,
                fixed: 'left'
            });
            console.log(this.lines);
        },
        addUpdate(rec) {
            // console.log(rec);
            let item = null;
            console.log(rec);
            if (rec.slot_id) {
                let i = this.checkTime(rec.slot_id, rec.time[0], rec.time[1]);
                if (!i) {
                    this.$emit('notify', 'warning', 'В это время работник находится на другой линии. Скорректируйте график работника или работу линии.')
                }
            }
            if(rec.new) {
                // New slot
                item = this.updSlots.find(el => el.worker_id == rec.worker_id && el.new == true && el.line_id == rec.line_id);
                // console.log(item);
            } else if (rec.slot_id && !rec.worker_id) {
                // Edit slot
                item = this.updSlots.find(el => el.slot_id == rec.slot_id);
            } else if (rec.worker_id && !rec.slot_id) {
                // Edit worker
                item = this.updWorkers.find(el => el.worker_id == rec.worker_id);
            }
            if (item) {
                // console.log(rec);
                item.started_at = rec.time[0].format('HH:mm');
                item.ended_at = rec.time[1].format('HH:mm');
            } else {
                // console.log(item);
                // console.log(rec);
                if (rec.new) {
                    // console.log('new');
                } else if (rec.slot_id) {
                    this.updSlots.push({
                        slot_id: rec.slot_id ? rec.slot_id : -1,
                        started_at: rec.time[0].format('HH:mm'),
                        ended_at: rec.time[1].format('HH:mm')
                    });
                } else if (rec.worker_id) {
                    this.updWorkers.push({
                        worker_id: rec.worker_id,
                        started_at: rec.time[0].format('HH:mm'),
                        ended_at: rec.time[1].format('HH:mm')
                    });
                }
            }
        },
        updateData(upd) {
            if (upd) {
                if (this.updSlots) {
                    axios.post('/api/edit_slot',
                        this.updSlots
                    ).catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                    });
                }

                if (this.updWorkers) {
                    axios.post('/api/save_worker',
                        this.updWorkers
                    ).catch((err) => {
                        this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                    });
                }
            }
            this.edit = ref(false);
            this.$emit('close-modal', upd);
        },
        checkTime(slot_id, time0, time1) {
            let worker = this.slots.find(el => el.slot_id == slot_id).worker_id;
            if (worker) {
                let sl = this.slots.filter(el => { return (el.worker_id == worker && el.slot_id != slot_id)});
                if (sl.length > 0) {
                    return sl.filter(slot => 
                            (time0.isBefore(this.ft(slot.started_at)) && time1.isBefore(this.ft(slot.started_at))) ||
                            (time0.isAfter(this.ft(slot.ended_at)) && time1.isAfter(this.ft(slot.ended_at))) 
                    ).length == sl.length;
                } else {
                    return true;
                }
            }
        },
        ft(timeString) {
            let a = new Date();
            let spl = timeString.split(":");
            a.setHours(spl[0]);
            a.setMinutes(spl[1]);
            return a.toISOString();
        },
        addSlotFront(record, line_id) {
            let id = this.workers.find(el => record.worker_id == el.worker_id);
            if (id) {
                id[line_id] = {
                    time: ref([dayjs.default(), dayjs.default()]),
                    worker_id: record.worker_id,
                    line_id: line_id,
                    new: true
                };
                this.updSlots.push({
                    worker_id: record.worker_id,
                    new: true,
                    line_id: line_id,
                    started_at: id[line_id].time[0],
                    ended_at: id[line_id].time[1]
                });

                // console.log(this.updSlots);
            }
        },
        checkStatus(record, index) {
            if (record.worker_id || !record.time || !record.time[index]) {
                return null;
            }
            let lineObj = this.lines.find(el => el.line_id == record.line_id);
            if (!lineObj) {
                return "error";
            }
            if (record.time[index].isBefore(lineObj.started_at) || record.time[index].isAfter(lineObj.ended)) {
                return "error";
            }
            console.log("status is ok");
        }
    },
    updated() {
        // if (this.$props.data && !this.slots) {
            this.slots = this.$props.data.slots.slice();
            this.lines = this.$props.data.lines.slice();

            this.workers = this.$props.data.workers.slice();
            this.processRows();
            this.processColumns();
        // }
    },
    mounted() {
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
    <Modal v-model:open="$props.open" cancelText="Закрыть" title="Редактировать график" @ok="updateData(true)"
        @cancel="updateData(false)" style="width:98vw;">
        <Switch checked-children="Редактирование" un-checked-children="Просмотр" v-model:checked="edit" />
        <br />
        <br />
        <div class="table-container">
            <Table :dataSource="workers" :columns="lines.filter(el => el.done == false || !el.line_id)" :pagination="{ pageSize: 6 }" small :scroll="{x: lines.filter(el => el.done == false || !el.line_id).length * 150}"
                style="scrollbar-color: unset;">
                <template #headerCell="{ column }">
                    <div v-if="column.dataIndex != 'title' && column.dataIndex != 'break'" style="display: flex;flex-direction: column;align-items: center;">
                        <span style="text-align:center">{{ column.title }}</span>
                        <br>
                        <span style="color:gray" v-if="column.started_at && column.ended_at">{{ column.started_at.format('HH:mm') }} - {{ column.ended_at.format('HH:mm') }}</span>
                    </div>
                    <div v-else style="display: flex;flex-direction: column;align-items: center;">
                        <span>{{ column.title }}</span>
                    </div>
                </template>
                <template #bodyCell="{ column, record, text }">
                    <template v-if="column.dataIndex != 'title' &&
                        record[column.dataIndex]">
                        <template v-if="!edit">
                            <div style="text-align:center">
                                {{ record[column.dataIndex]['time'][0].format('HH:mm') }} - {{
                                    record[column.dataIndex]['time'][1].format('HH:mm') }}
                            </div>
                        </template>
                        <template v-else >
                            <div style="display:flex;justify-content: space-between;flex-direction: row;">
                                <TimePicker v-model:value="record[column.dataIndex]['time'][0]"
                                    @change="(ev) => { addUpdate(record[column.dataIndex]); }" format="HH:mm"
                                    :showTime="true" :allowClear="true" type="time" :showDate="false" size="small"
                                    style="border-color: #1677ff;width:47%;" />
                                    <!-- :status="checkStatus(record[column.dataIndex], 0)"/> -->
                                <span> - </span>
                                <TimePicker v-model:value="record[column.dataIndex]['time'][1]"
                                    @change="(ev) => { addUpdate(record[column.dataIndex]); }" format="HH:mm"
                                    :showTime="true" :allowClear="true" type="time" :showDate="false" size="small"
                                    style="border-color: #1677ff;width:47%;" />
                                    <!-- :status="checkStatus(record[column.dataIndex], 1)"/> -->
                            </div>
                        </template>
                    </template>
                    <template v-else-if="!record[column.dataIndex] && edit">
                        <Button type="dashed" @click="addSlotFront(record, column.dataIndex)" style="width: 100%;padding:0;">+</Button>
                    </template>
                    <template v-else>
                        {{ record[column.dataIndex] }}
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
</template>