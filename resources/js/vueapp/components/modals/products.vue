<script setup lang="ts">
import { CategoryInfo, useCategoriesStore } from '@/store/categories';
import { useModalsStore } from '@/store/modal';
import { ProductInfo, useProductsStore } from '@/store/products';
import { ProductSlot, useProductsSlotsStore } from '@/store/productsSlots';
import { Modal, Divider, Tree, List, ListItem, Input, Tabs, TabPane, Empty, Button, InputNumber, Select, SelectOption, Checkbox, Table, TableSummary, TableSummaryRow, TableSummaryCell } from 'ant-design-vue';
import { Key } from 'ant-design-vue/es/_util/type';
import { computed, ref, Ref } from 'vue';
import { EditOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons-vue';
import { hardwares, productsTableColumns, productsTabs } from '@/store/dicts';
import { useLinesStore } from '@/store/lines';

const modal = useModalsStore();
const categoriesStore = useCategoriesStore();
const productsStore = useProductsStore();
const slotsStore = useProductsSlotsStore();
const linesStore = useLinesStore();

const activeCategory: Ref<CategoryInfo> = ref();
const products: Ref<ProductInfo[]> = ref([]);
const slots: Ref<Object> = ref([]);

const activeProduct: Ref<ProductInfo> = ref();
const activeTab: Ref<string> = ref('1');

const handleCategorySelect = async (key: Key[]) => {
    activeCategory.value = categoriesStore.getByID(Number(key[0]));
    products.value = productsStore.getByCategoryID(Number(key[0]));
}
const handleProductSelect = async (key: number) => {
    activeProduct.value = productsStore.getByID(key);
    if (activeTab.value != '3') {
        slots.value = {
            1: slotsStore.getByTypeAndProductID(
            activeProduct.value.product_id, 1
            ),
            2: slotsStore.getByTypeAndProductID(
            activeProduct.value.product_id, 2
            )
        }
    }
    console.log(slots);
}

/* PRODUCTS */
const addProduct = () => products.value.push(productsStore.add(activeCategory.value));
const editProduct = (product: ProductInfo) => product.isEditing = true;    
const saveProduct = (product: ProductInfo) => {
    if (product.product_id) {
        productsStore._update(product);
    } else {
        productsStore._create(product);
    }
    product.isEditing = false;
};
const deleteProduct = (product: ProductInfo) => {
    productsStore._delete(product);
    products.value = products.value.filter((el: ProductInfo) => el != product);
}

/* SLOTS */
const addSlot = () => slots.value[activeTab.value].push(slotsStore.add(activeProduct.value, Number(activeTab.value), linesStore.lines.at(0).line_id));
const editSlot = (slot: ProductSlot) => slot.isEditing = true;
const saveSlot = (slot: ProductSlot) => {
    if (slot.product_slot_id) {
        slotsStore._update(slot);
    } else {
        slotsStore._create(slot);
    }
    slot.isEditing = false;
};
const deleteSlot = (slot: ProductSlot) => slotsStore._delete(slot);

const getClass = (item: ProductInfo) => {
    return activeProduct.value && activeProduct.value.product_id == item.product_id ? 'active' : '';
}

/**
 * Закрыть окно
 */
const exit = () => {
    // TODO сообщение о несохранённых изменениях
    modal.close('products');
}

</script>
<template>
    <Modal v-model:open="modal.visibility['products']" title="Реестр продукции" :closable="true"
        wrap-class-name="modal products" class="modal products" @close="exit">
        <div class="products">
            <section class="products">
                <Tree :tree-data="categoriesStore.asTree()" @select="handleCategorySelect" />
            </section>
            <Divider type="vertical" style="height:unset;" />
            <section class="products">
                <!-- TODO лишние стили мб -->
                <!-- <List :data-source="products" v-if="products.length != 0" style="max-height:60vh; overflow: auto;"> -->
                <List :data-source="products" v-if="products.length != 0" class="product_list">
                    <template #renderItem="{ item }">
                        <ListItem v-if="!item.isEditing" class="product_list-item"
                            :class="getClass(item)">
                            <a href="#" @click="handleProductSelect(item.product_id)">{{ item.title }}</a>
                            <div class="icons-container">
                                <EditOutlined @click="editProduct(item)" class="icon edit" />
                                <DeleteOutlined @click="deleteProduct(item)" class="icon delete" />
                            </div>
                        </ListItem>
                        <ListItem v-else class="product_list-item" style="display: flex;">
                            <Input v-model:value="item.title" style="max-width:88%" />
                            <SaveOutlined @click="saveProduct(item)" class="icon save" />
                        </ListItem>
                    </template>
                    <template #footer>
                        <Button @click="addProduct" type="primary" class="footer-button">+</Button>
                    </template>
                </List>
                <template v-else>
                    <Empty description="Выберите категорию" style="max-width:100%;" />
                    <Button @click="" type="primary" class="footer-button">+</Button>
                </template>
            </section>
            <Divider type="vertical" style="height:unset;" />
            <section style="width: 60%; ">
                <Tabs v-model:activeKey="activeTab">
                    <TabPane v-for="(v, k) in productsTabs" :key="k" :tab="v">
                        <template v-if="k == 3">
                            <div style="display:flex; flex-direction: column; gap: 10px;">
                                <div v-for="(v) in productsTableColumns[k]" style="display: flex;">
                                    <span style="width:20%;padding-right:5%;">{{ v.title }}</span>
                                    <div v-if="v.addon !== false">
                                        <Input v-model:value="activeProduct[v.dataIndex]" style="max-width:300px;"
                                            :addon-before="v.addon" />
                                    </div>
                                    <div v-else>
                                        <Checkbox v-model:checked="activeProduct[v.dataIndex]" />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <Empty v-if="!activeProduct" description="Выберите продукцию" />
                            <Table :columns="productsTableColumns[k]" bordered small :data-source="slots[k]"
                                :pagination="false" v-else style="max-width: 100%;">
                                <template #emptyText>
                                    <Empty description="Для данной продукции пока нет слотов изготовления" />
                                </template>
                                <template #bodyCell="{ record, column, text }">
                                    <template v-if="record.isEditing">
                                        <template
                                            v-if="['people_count', 'perfomance'].find(el => el == column.dataIndex)">
                                            <InputNumber v-model:value="record[String(column.dataIndex)]" />
                                        </template>
                                        <template v-else-if="column.dataIndex == 'hardware' && k == 1">
                                            <Select v-model:value="record[column.dataIndex]" style="width: 100%;"
                                                :options="hardwares">
                                            </Select>
                                        </template>
                                        <template v-else-if="column.dataIndex == 'actions'">
                                            <SaveOutlined @click="saveSlot(record as ProductSlot)" class="icon save"/>
                                            <DeleteOutlined @click="deleteSlot(record as ProductSlot)"
                                                class="icon delete" />
                                        </template>
                                        <template v-else>
                                            <Select v-model:value="record[String(column.dataIndex)]"
                                                style="width:100%; max-width:23vw;">
                                                <SelectOption v-for="i in linesStore.lines" :key="i.line_id"
                                                    :value="i.line_id">
                                                    {{ i.title }}
                                                </SelectOption>
                                            </Select>
                                        </template>
                                    </template>
                                    <template v-else>
                                        <template v-if="column.dataIndex == 'actions'">
                                            <EditOutlined @click="editSlot(record as ProductSlot)" class="icon edit"/>
                                            <DeleteOutlined @click="deleteSlot(record as ProductSlot)"
                                                class="icon delete" />
                                        </template>
                                        <template v-if="column.dataIndex == 'line_id'">
                                            {{ linesStore.getByID(text)!.title }}
                                        </template>
                                        <template v-if="column.dataIndex == 'hardware' && text">
                                            {{ hardwares[text]!.label }}
                                        </template>
                                    </template>
                                </template>
                                <template #summary>
                                    <TableSummary v-if="activeProduct != null">
                                        <TableSummaryRow>
                                            <TableSummaryCell :col-span="5" style="padding:0;">
                                                <Button type="primary" @click="addSlot"
                                                    style="width: 100%;border-top-left-radius: 0; border-top-right-radius: 0;">+</Button>
                                            </TableSummaryCell>
                                        </TableSummaryRow>
                                    </TableSummary>
                                </template>
                            </Table>
                        </template>
                    </TabPane>
                </Tabs>
            </section>
        </div>
        <template #footer></template>
    </Modal>
</template>