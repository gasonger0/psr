<script setup lang="ts">
import { FileExcelOutlined } from '@ant-design/icons-vue';
import { Button, Card, Popconfirm, Switch } from 'ant-design-vue';
import { computed, onBeforeMount, ref, Ref } from 'vue';
import { ProductInfo, useProductsStore } from '@stores/products';

const productsStore = useProductsStore();

const showList: Ref<boolean> = ref(false);
const hideEmpty: Ref<boolean> = ref(false);
const categorySwitch: Ref<boolean> = ref(false);


const clearPlan = () => {
    // TODO plansStore._clear();
}

const prodListTitle = computed(() => {
    return showList.value ? 'Скрыть' : 'Показать список продукции';
});
const hideEmptyLinesTitle = computed(() => {
    return hideEmpty.value ? 'Показать пустые линии' : 'Скрыть пустые линии';
});
const categorizedProducts = computed(() => {
    return productsStore.products.filter((el: ProductInfo) => el.category.type.value == categorySwitch.value);
});

</script>
<template>
    <section class="plans-toolbar">
        <Button type="dashed" @click="() => showList = !showList">
            {{ prodListTitle }}
        </Button>
        <Button type="dashed" @click="() => hideEmpty = !hideEmpty">
            {{ hideEmptyLinesTitle }}
        </Button>
        <Button type="primary" class="excel-button">
            <FileExcelOutlined />
            Скачать XLSX
        </Button>
        <Popconfirm title="Это действие удалит весь план продукции" okText="Очистить" cancelText="Отмена"
            @confirm="clearPlan">
            <Button type="primary">
                Очистить план
            </Button>
        </Popconfirm>
    </section>
    <section class="lines-container">
        <div class="line" data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" :headStyle="{ 'background-color': 'white' }">
                <template #title>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Продукция</span>
                        <Switch checked-children="Фасованная" un-checked-children="Весовая"
                            v-model:checked="categorySwitch" />
                    </div>
                </template>
            </Card>
            <section class="line_items products">

            </section>
        </div>
    </section>
</template>