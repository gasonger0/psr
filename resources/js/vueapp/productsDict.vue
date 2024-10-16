<script setup>
import { Input, InputNumber, Modal, Switch, Table } from 'ant-design-vue';
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
            edit: ref(false),
            columns: [{
                title: 'Наименование',
                dataIndex: 'title'
            }, {
                title: 'Количество человек',
                dataIndex: 'workers_count'
            }, {
                title: 'Длительность смены',
                dataIndex: 'duration'
            }, {
                title: 'Единиц в час',
                dataIndex: 'perfomance'
            }],
            measures: {
                workers_count: 'человек',
                duration: ' ч.',
                perfomance: ' ед./ч.'
            }
        }
    }
}
</script>
<template>
    <Modal v-model:open="$props.open">
        <Switch 
            v-model:checked="edit" 
            checked-children="Редактирование" 
            un-checked-children="Просмотр"
            class="title-switch" />
        <Table>
            <template #bodyCell="{ record, column, text}">
                <template v-if="edit">
                    <template v-if="['workers_count', 'duration','perfomance'].find(column.dataIndex) !== false">
                        <InputNumber v-model:value="record.workers_count"/> {{ measures[column.dataIndex] }}
                    </template>
                    <template v-else>
                        <Input v-model:value="record[column.dataIndex]" />
                    </template>
                </template>
            </template>
        </Table>
    </Modal>
</template>