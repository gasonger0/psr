<script setup>
import { Button, Divider, Empty, Input, InputNumber, Keyframes, List, ListItem, Modal, Select, SelectOption, Skeleton, Switch, Table, TableSummary, TableSummaryCell, TableSummaryRow, Tree } from 'ant-design-vue';
import axios from 'axios';
import { ref, reactive } from 'vue';


</script>
<script>
export default {
    props: {
        open: {
            type: Boolean
        },
        data: {
            type: Array
        }
    },
    data() {
        return {
            editing: ref(false),
            products: reactive([]),
            categories: reactive([]),
            slots: reactive([]),
            columns: [{
                title: 'Линия',
                dataIndex: 'line_id'
            }, {
                title: 'Количество струдников',
                dataIndex: 'people_count'
            }, {
                title: 'Длительность смены',
                dataIndex: 'duration'
            }, {
                title: 'Единиц в час',
                dataIndex: 'perfomance'
            }],
            measures: {
                people_count: ' человек',
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
                            resolve();
                        }
                    })
                    .catch((err) => {
                        console.log(err);
                        this.$emit('notify', 'Что-то пошло не так');
                        reject();
                    });
            });
        },
        getProducts(key) {
            axios.post('/api/get_products',
                'category_id=' + key[0])
                .then((response) => {
                    if (response.data) {
                        console.log(response.data);
                        this.products = response.data;
                    }
                })
                .catch((err) => {
                    console.log(err);
                    this.$emit('notify', 'Что-то пошло не так');
                });
        },
        getProductSlots(product_id) {
            axios.post('/api/get_product_slots', 'product_id=' + product_id)
                .then((response) => {
                    if (response.data) {
                        this.slots = response.data;
                    }
                })
                .catch((err) => {
                    console.log(err);
                    this.$emit('notify', 'error', 'Что-то пошло не так: ' + err.code);
                });
        },
        exit(save) {
            this.$emit('close-modal');
        }
    },
    async updated() {
        await this.getCategories();
        // this.getProducts();
    }
}
</script>
<template>
    <Modal v-model:open="$props.open" title="Реестр продукции" style="width: 90vw;height:80vh;" @ok="exit"
        :closable="false">
        <Switch v-model:checked="editing" checked-children="Редактирование" un-checked-children="Просмотр"
            class="title-switch" />
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 20%;">
                <Skeleton active v-if="categories.length == 0" />
                <Tree :tree-data="categories" @select="(key, e) => { getProducts(key) }" v-else>
                </Tree>
            </div>
            <Divider type="vertical" style="height:unset;" />
            <div style="width:20%">
                <List :data-source="products" v-if="products.length != 0">
                    <template #renderItem="{ item }">
                        <ListItem @click="getProductSlots(item.product_id)"><a href="#">{{ item.title }}</a></ListItem>
                        <Divider type="vertical" />
                    </template>
                </List>
                <Empty description="Нет данных" v-else style="max-width:100%;" />
            </div>
            <Divider type="vertical" style="height:unset;" />
            <Table :columns="columns" style="min-width: 60%;" bordered :data-source="slots" :pagination="false">
                <template #emptyText>
                    <Empty description="Нет данных" style="max-width:100%;" />
                </template>
                <template #bodyCell="{ record, column, text }">
                    <template v-if="editing">
                        <template v-if="['people_count', 'duration', 'perfomance'].find(el => el == column.dataIndex)">
                            <InputNumber v-model:value="record[column.dataIndex]" /> {{ measures[column.dataIndex] }}
                        </template>
                        <template v-else>
                            <Select v-model:value="record[column.dataIndex]" style="max-width: 250px;">
                                <SelectOption v-for="i in $props.data.lines" :key="i.line_id" :value="i.line_id">
                                    {{ i.title }}
                                </SelectOption>
                            </Select>
                        </template>
                    </template>
                    <template v-else-if="column.dataIndex == 'line_id'">
                        <span>{{ $props.data.lines.find(el => el.line_id == text).title }}</span>
                    </template>
                </template>
                <template #summary>
                    <TableSummary v-if="editing">
                        <TableSummaryRow>
                            <TableSummaryCell :col-span="4" style="padding:0;">
                                <Button type="primary" style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                            </TableSummaryCell>
                        </TableSummaryRow>
                    </TableSummary>
                </template>
            </Table>
        </div>
        <template #footer>
            <Button type="primary" @click="exit(true)">Сохранить</Button>
            <Button type="default" @click="exit(false)">Закрыть</Button>
        </template>
    </Modal>
</template>