<script setup>
import { Divider, Input, InputNumber, Modal, Skeleton, Switch, Table, Tree } from 'ant-design-vue';
import axios from 'axios';
import { ref, reactive } from 'vue';


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
            products: reactive([]),
            categories: reactive([]),
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
                workers_count: ' человек',
                duration: ' ч',
                perfomance: ' кг/ч'
            }
        }
    },
    methods: {
        getCategories() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_categories')
                    .then((response) => {
                        if (response.data) {
                            console.log(response.data);
                            this.categories = response.data;
                            resolve(true);
                        }
                    });
            });
        },
        getProducts() {
            axios.get('/api/get_products')
                .then((response) => {
                    if (response.data) {
                        console.log(response.data);
                        this.products = response.data;
                    }
                });
        }
    },
    async updated() {
        await this.getCategories();
        this.getProducts();
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" title="Реестр продукции" cancelText="Закрыть" okText="Сохранить"
        style="width: 80vw;height:60vh;">
        <Switch v-model:checked="edit" checked-children="Редактирование" un-checked-children="Просмотр"
            class="title-switch" />
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 15%;">
                <Skeleton active v-if="categories.length == 0" />
                <Tree :tree-data="categories" :showLine="true" v-else>
                </Tree>
            </div>
            <Divider type="vertical" style="height:unset;"/>
            <Table :columns="columns" :dataSource="products" style="min-width: 70%;" bordered>
                <template #bodyCell="{ record, column, text }">
                    <template v-if="edit">
                        <template v-if="['workers_count', 'duration', 'perfomance'].find(column.dataIndex) !== false">
                            <InputNumber v-model:value="record.workers_count" /> {{ measures[column.dataIndex] }}
                        </template>
                        <template v-else>
                            <Input v-model:value="record[column.dataIndex]" />
                        </template>
                    </template>
                </template>
            </Table>
        </div>
    </Modal>
</template>