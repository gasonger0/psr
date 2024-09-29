<script setup>
import { Input, List, ListItem, Modal, Select, SelectOption } from 'ant-design-vue';
import axios from 'axios';
</script>
<script>
export default {
    props: {
        data: {
            type: Array
        }
    },
    data() {
        return {
            changes: []
        }
    },
    methods: {
        close(ev) {
            if (ev) {
                axios.post('/api/change_lines',
                    this.changes
                ).then((response) => {
                    this.$emit('notify', 'success', 'Линии рабочих успешно изменены');
                }).catch((err) => {
                    this.$emit('notify', 'error', 'Что-то пошло не так');
                })
            }
            this.$emit('close-modal');
        },
        writeChanges(line_id, worker_id, slot_id) {
            this.changes.push({
                worker_id: worker_id,
                line_id: line_id,
                slot_id: slot_id
            });
        }
    },
}
</script>
<template>
    <Modal v-model:open="$props.open" cancelText="Закрыть" okText="Сохранить" @ok="close(true)" @cancel="close(false)"
        :closable="false" style="width:50vw;">
        <div class="table-container">
            <List bordered :data-source="$props.data.workers">
                <template #renderItem="{ item }">
                    <ListItem>
                        <div style="display: flex;justify-content: space-between;width:100%;">
                            <span style="width: 130px;">{{ item.title }}</span>
                            <div style="width: 100px;">
                                <span v-if="item.current_line_id">На линии</span>
                                <span v-else style="color: red;">Не на линии</span>
                            </div>
                            <Select v-model:value="item.current_line_id" style="width:250px;"
                                @change="(value) => { writeChanges(value, item.worker_id, item.current_slot) }">
                                <SelectOption v-model:value="line.line_id" v-model:key="line.line_id"
                                    v-for="line in $props.data.lines">{{
                                        line.title }}</SelectOption>
                            </Select>
                        </div>
                    </ListItem>
                </template>
            </List>
        </div>
    </Modal>
</template>