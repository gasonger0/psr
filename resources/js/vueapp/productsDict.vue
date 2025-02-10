<script setup>
import { DeleteOutlined, EditOutlined, SaveOutlined } from '@ant-design/icons-vue';
import { Button, Divider, Empty, Input, InputNumber, Keyframes, List, ListItem, Modal, Select, SelectOption, Skeleton, Switch, Table, TableSummary, TableSummaryCell, TableSummaryRow, TabPane, Tabs, Tree, Checkbox } from 'ant-design-vue';
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
            editing: ref([]),
            products: reactive([]),
            categories: reactive([]),
            slots: reactive([]),
            loading: ref(false),
            activeProduct: ref(null),
            activeCategory: ref(null),
            activeTab: ref('1'),
            tabs: {
                1: "Варка",
                2: "Упаковка"
            },
            columnsPack: [{
                title: 'ИД',
                dataIndex: 'product_slot_id',
            },{
                title: 'Линия',
                dataIndex: 'line_id',
                width: '40%'
            }, {
                title: 'Оборудование',
                dataIndex: 'hardware'
            }, {
                title: 'Кол-во сотрудников',
                dataIndex: 'people_count'
            }, {
                title: 'Кг в час',
                dataIndex: 'perfomance'
            }],
            columns: [{
                title: 'ИД',
                dataIndex: 'product_slot_id',
                width: '5%'
            },{ 
                title: 'Линия',
                dataIndex: 'line_id',
                width: '40%'
            }, {
                title: 'Оборудование',
                dataIndex: 'hardware',
                width: '15%'
            }, {
                title: 'Кол-во сотрудников',
                dataIndex: 'people_count',
                width: '5%'
            }, {
                title: 'Кг в час',
                dataIndex: 'perfomance',
                width: '5%'
            }],
            specColumns: [
                { title: 'Штук в Ящике:', dataIndex: 'amount2parts', addon: '' },
                { title: 'Штуки в Кг:', dataIndex: 'parts2kg', addon: 'Шт ×' },
                { title: 'Кг в Варки:', dataIndex: 'kg2boil', addon: 'Кг ×' },
                { title: 'Телеги:', dataIndex: 'cars', addon: 'Варка ×' },
                { title: 'Поддоны:', dataIndex: 'cars2plates', addon: '(Варка - Варка(цел)) ×' },
                { title: 'Отображать, даже если нет в анализе', dataIndex: 'always_show', addon: false },
            ],
            hardwares: [
                { value: 1, label: 'ТОРНАДО' },
                { value: 2, label: 'Мондомикс' },
                { value: 3, label: 'Китайский Аэрос' }
            ],
            packHardwares: [
                { value: 4, label: 'ЗМ №1'},
                { value: 5, label: 'ЗМ №2'},
                { value: 6, label: 'ЗМ №1, №2'},
            ]
        }
    },
    methods: {
        getCategories() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_categories')
                    .then((response) => {
                        if (response.data) {
                            this.categories = response.data;
                            resolve();
                        }
                    })
                    .catch((err) => {
                        // console.log(err);
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
                        // console.log(response.data);
                        this.products = response.data;
                        this.loading = false;
                    }
                })
                .catch((err) => {
                    // console.log(err);
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
                            this.slots = response.data.map(el => {
                                el.hardware = el.hardware ? Number(el.hardware) : null;
                                return el;
                            });
                            this.loading = false;

                        }
                    })
                    .catch((err) => {
                        // console.log(err);
                        this.$emit('notify', 'error', 'Что-то пошло не так: ' + err.code);
                    });
            }, 500);
        },
        deleteProduct(product_id) {
            axios.post('/api/delete_product', { product_id: product_id })
                .then(response => {
                    this.products.splice(this.products.indexOf(this.products.find(el => el.product_id == product_id)), 1);
                    this.$emit('notify', 'success', 'Продукция удалена');
                })
                .catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
                });
        },
        exit() {
            this.activeCategory = null;
            window.location.reload();
            this.activeProduct = null;
            this.$emit('close-modal', true);
        },
        addProductFront() {
            this.products.push({
                product_id: -1,
                title: null,
                category_id: this.activeCategory
            });
            this.slots = false;
            this.editing.push(-1);
        },
        saveProduct(product_id) {
            let product = this.products.find(el => el.product_id == product_id);
            if (product) {
                axios.post('/api/save_product', product)
                    .then(response => {
                        this.$emit('notify', 'success', 'Продукция сохранена');
                        this.editing.splice(this.editing.indexOf(product_id), 1);
                    });
            }
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
                /*.map(el => {
                    if (typeof el.hardware == 'object') {
                        if (el.hardware.length > 1) {
                            el.hardware = el.hardware.join(',');
                        } else {
                            el.hardware = el.hardware[0];
                        }
                    }
                    return el;
                })*/
            ).then((response) => {
                if (response.data) {
                    this.$emit('notify', 'success', 'Изменения сохранены');
                    for(let i = 0; i < response.data; i++) {
                        this.slots[$i]['product_slot_id'] = response.data[i];
                    }
                }
            });
            this.saveProduct(this.activeProduct.product_id);
        },
        deleteSlot(slot_id) {
            axios.post('/api/delete_product_slot', { product_slot_id: slot_id })
                .then(response => {
                    this.slots.splice(this.slots.indexOf(this.slots.find(el => el.product_slot_id == slot_id)), 1);
                    this.$emit('notify', 'success', 'Изменения сохранены');
                })
                .catch((err) => {
                    this.$emit('notify', 'error', "Что-то пошло не так: " + err.code);
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
        <!-- <Switch v-model:checked="editing" checked-children="Редактирование" un-checked-children="Просмотр"
            class="title-switch" /> -->
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 20%;">
                <Skeleton active v-if="categories.length == 0" />
                <Tree :tree-data="categories" @select="(key, e) => { getProducts(key) }" v-else>
                </Tree>
            </div>
            <Divider type="vertical" style="height:unset;" />
            <div style="width:20%">
                <List :data-source="products" v-if="products.length != 0" style="max-height:60vh; overflow: auto;">
                    <template #renderItem="{ item }">
                        <ListItem v-if="!editing.find(el => el == item.product_id)" class="product_list-item"
                            :class="activeProduct && activeProduct.product_id == item.product_id ? 'active' : ''"
                            style="justify-content: space-between;">
                            <a href="#" @click="getProductSlots(item.product_id)">{{ item.title }}</a>
                            <div
                                style="display: flex; flex-direction: column; min-height: 50px; justify-content: space-between;">
                                <EditOutlined @click="editing.push(item.product_id)"
                                    style="color: white; background-color: #1677ff; padding:5px;border-radius: 3px;" />
                                <DeleteOutlined @click="deleteProduct(item.product_id)"
                                    style="color: white; background-color: #bd1515; padding:5px;border-radius: 3px;" />
                            </div>
                        </ListItem>
                        <ListItem v-else class="product_list-item" style="display: flex;">
                            <Input v-model:value="item.title" style="max-width:88%" />
                            <SaveOutlined @click="saveProduct(item.product_id)"
                                style="color: white; background-color: #1677ff; padding:5px;border-radius: 3px;" />
                        </ListItem>
                    </template>
                    <template #footer>
                        <Button @click="addProductFront" type="primary" style="width:100%"
                            v-if="!editing.find(el => el == -1) && activeCategory != null">+</Button>
                    </template>
                </List>
                <template v-else-if="loading">
                    <Skeleton active />
                </template>
                <template v-else>
                    <Empty description="Нет данных" style="max-width:100%;" />
                    <Button @click="addProductFront" type="primary" style="width:100%"
                        v-if="activeCategory != null">+</Button>
                </template>
            </div>
            <Divider type="vertical" style="height:unset;" />
            <div style="min-width: 60%;">
                <Tabs v-model:activeKey="activeTab">
                    <template #rightExtra>
                        <Button type="primary" @click="addProductSlot">Сохранить</Button>
                    </template>
                    <TabPane v-for="(v, k) in tabs" :key="k" :tab="v">
                        <Table :columns="k == 1 ? columns : columnsPack" bordered small
                            :data-source="slots.filter(el => { return el.type_id == k })" :pagination="false">
                            <template #emptyText>
                                <template v-if="loading">
                                    <Skeleton active />
                                </template>
                                <template v-else>
                                    <Empty description="Нет данных" style="max-width:100%;" />
                                </template>
                            </template>
                            <template #bodyCell="{ record, column, text }">
                                <template v-if="editing">
                                    <template v-if="['people_count', 'perfomance'].find(el => el == column.dataIndex)">
                                        <InputNumber v-model:value="record[column.dataIndex]" />
                                    </template>
                                    <template v-else-if="column.dataIndex == 'hardware'">
                                        <Select v-model:value="record[column.dataIndex]" style="width: 100%;"
                                            :options="k == 1 ? hardwares : packHardwares">
                                            <!-- <SelectOption v-for="(v, k) in hardwares" :key="k" :value="k">
                                                {{ v }}
                                            </SelectOption> -->
                                        </Select>
                                    </template>
                                    <template v-else-if="column.dataIndex == 'product_slot_id'">
                                        <DeleteOutlined @click="deleteSlot(record.product_slot_id)"/>
                                    </template>
                                    <template v-else>
                                        <Select v-model:value="record[column.dataIndex]" style="width:100%; max-width:23vw;">
                                            <SelectOption v-for="i in $props.data.lines" :key="i.line_id"
                                                :value="i.line_id">
                                                {{ i.title }}
                                            </SelectOption>
                                        </Select>
                                    </template>
                                </template>
                                <!-- <template v-else-if="column.dataIndex == 'line_id'">
                                    <span>{{ $props.data.lines.find(el => el.line_id == text).title }}</span>
                                </template> -->
                            </template>
                            <template #summary>
                                <TableSummary v-if="activeProduct != null">
                                    <TableSummaryRow>
                                        <TableSummaryCell :col-span="5" style="padding:0;">
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
                                <span style="width:20%;padding-right:5%;">{{ v.title }}</span>
                                <div v-if="v.addon !== false">
                                    <Input v-if="editing" v-model:value="activeProduct[v.dataIndex]"
                                        style="max-width:300px;" :addon-before="v.addon" />
                                    <!-- <span v-else>
                                        {{ v.addon + ' ' + activeProduct[v.dataIndex] ? activeProduct[v.dataIndex] : '-'
                                        }}
                                    </span> -->
                                </div>
                                <div v-else>
                                    <Checkbox v-model:checked="activeProduct[v.dataIndex]" :disabled="!editing" />
                                </div>

                            </div>
                        </div>
                    </TabPane>
                </Tabs>

            </div>
        </div>
        <template #footer>
            <Button type="default" @click="exit()">Закрыть</Button>
        </template>
    </Modal>
</template>