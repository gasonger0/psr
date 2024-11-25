<script setup>
import { Button, Divider, Empty, Input, InputNumber, Keyframes, List, ListItem, Modal, Select, SelectOption, Skeleton, Switch, Table, TableSummary, TableSummaryCell, TableSummaryRow, TabPane, Tabs, Tree } from 'ant-design-vue';
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
            loading: ref(false),
            activeProduct: ref(-1),
            activeCategory: ref(-1),
            activeTab: ref('1'),
            tabs: {
                1: "Варка",
                2: "Упаковка"
            },
            columns: [{
                title: 'Линия',
                dataIndex: 'line_id',
                width: '40%'
            }, {
                title: 'Количество струдников',
                dataIndex: 'people_count'
            }, {
                title: 'Кг в час',
                dataIndex: 'perfomance'
            }],
            measures: {
                people_count: ' человек',
                perfomance: ' кг/ч'
            },
            specColumns: [
                { title: 'Штук в Ящике:',   dataIndex: 'amount2parts',  addon: ''                       },
                { title: 'Штуки в Кг:',     dataIndex: 'parts2kg',      addon: 'Шт ×'                   },
                { title: 'Кг в Варки:',     dataIndex: 'kg2boil',       addon: 'Кг ×'                   },
                { title: 'Тачки:',          dataIndex: 'cars',          addon: 'Варка ×'                },
                { title: 'Поддоны:',        dataIndex: 'cars2plates',   addon: '(Варка - Варка(цел)) ×' }
            ]
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
            this.activeCategory = key[0];
            this.loading = true;
            this.slots = reactive([]);
            this.products = reactive([]);
            axios.post('/api/get_products',
                'category_id=' + key[0])
                .then((response) => {
                    if (response.data) {
                        console.log(response.data);
                        this.products = response.data;
                        this.loading = false;
                    }
                })
                .catch((err) => {
                    console.log(err);
                    this.$emit('notify', 'Что-то пошло не так');
                });
        },
        getProductSlots(product_id) {
            if (product_id == -1) {
                this.addProducts();
            }
            this.loading = true;
            this.activeProduct = this.products.find(el => el.product_id == product_id);
            this.slots = reactive([{}]);
            setTimeout(() => {
                axios.post('/api/get_product_slots', 'product_id=' + product_id)
                    .then((response) => {
                        if (response.data) {
                            this.slots = response.data;

                            this.loading = false;
                        }
                    })
                    .catch((err) => {
                        console.log(err);
                        this.$emit('notify', 'error', 'Что-то пошло не так: ' + err.code);
                    });
            }, 500);
        },
        exit(save) {
            if (save) {
                if (this.products.find(el => el.product_id == this.activeProduct.product_id)) {
                    this.addProducts();
                    this.addProductSlot();
                    this.getProducts(this.activeCategory);
                } else {
                    this.addProductSlot();
                    this.getProductSlots(this.activeProduct.product_id);
                }
                this.editing = false;
            } else {
                this.$emit('close-modal', true);
            }
        },
        addProductFront() {
            this.products.push({
                product_id: -1,
                title: null,
                category_id: this.activeCategory
            });
            this.slots = false;
        },
        addProducts() {
            let index = this.products.find(el => el.product_id == this.activeProduct.product_id);
            if (index) {
                this.products[index] = this.activeProduct;
            }
            console.log(index);
            axios.post('/api/add_products', this.products)
                .then(response => {
                    this.$emit('notify', 'success', 'Продукция добавлена');
                });
        },
        addSlotFront(k) {
            this.slots.push({
                product_slot_id: -1,
                product_id: this.activeProduct.product_id,
                line_id: null,
                people_count: 0,
                perfomance: 0,
                type_id: k,
                order: this.slots.filter(el => { return el.type_id == k }).length + 1
            });
        },
        addProductSlot() {
            axios.post('/api/add_product_slots',
                this.slots
            ).then((response) => {

            });
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
                        <ListItem @click="getProductSlots(item.product_id)" v-if="!editing" class="product_list-item"
                            :class="activeProduct.product_id == item.product_id ? 'active' : ''">
                            <a href="#">{{ item.title }}</a>
                        </ListItem>
                        <ListItem v-else class="product_list-item">
                            <Input v-model:value="item.title" />
                        </ListItem>
                    </template>
                    <template #footer>
                        <Button @click="addProductFront" type="primary" style="width:100%" v-if="editing">+</Button>
                    </template>
                </List>
                <template v-else-if="loading">
                    <Skeleton active />
                </template>
                <template v-else>
                    <Empty description="Нет данных" style="max-width:100%;" />
                    <Button @click="addProductFront" type="primary" style="width:100%" v-if="editing">+</Button>
                </template>
            </div>
            <Divider type="vertical" style="height:unset;" />
            <div style="min-width: 60%;">
                <Tabs v-model:activeKey="activeTab">
                    <TabPane v-for="(v, k) in tabs" :key="k" :tab="v">
                        <Table :columns="columns" bordered :data-source="slots.filter(el => { return el.type_id == k })"
                            :pagination="false" v-show=slots>
                            <template #emptyText>
                                <Empty description="Нет данных" style="max-width:100%;" />
                            </template>
                            <template #bodyCell="{ record, column, text }">
                                <template v-if="loading">
                                    <Skeleton active />
                                </template>
                                <template v-else-if="editing">
                                    <template v-if="['people_count', 'perfomance'].find(el => el == column.dataIndex)">
                                        <InputNumber v-model:value="record[column.dataIndex]" /> {{
                                            measures[column.dataIndex]
                                        }}
                                    </template>
                                    <template v-else>
                                        <Select v-model:value="record[column.dataIndex]" style="width: 100%;">
                                            <SelectOption v-for="i in $props.data.lines" :key="i.line_id"
                                                :value="i.line_id">
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
                                            <Button type="primary" @click="addSlotFront(k)"
                                                style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                                        </TableSummaryCell>
                                    </TableSummaryRow>
                                </TableSummary>
                            </template>
                        </Table>
                    </TabPane>
                    <TabPane :key="3" tab="Служебная информация">
                        <div style="display:flex; flex-direction: column; gap: 10px;">
                            <div v-for="(v) in specColumns" style="display: flex;">
                                <span style="width:20%;">{{ v.title }}</span>
                                <Input v-if="editing" v-model:value="activeProduct[v.dataIndex]" style="max-width:300px;" :addon-before="v.addon"/>
                                <span v-else>{{ v.addon }} {{ activeProduct[v.dataIndex] ? activeProduct[v.dataIndex] : '-'}}</span>
                            </div>
                        </div>
                    </TabPane>
                </Tabs>

            </div>
        </div>
        <template #footer>
            <Button type="primary" @click="exit(true)">Сохранить</Button>
            <Button type="default" @click="exit(false)">Закрыть</Button>
        </template>
    </Modal>
</template>