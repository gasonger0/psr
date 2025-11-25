<script setup lang="ts">
import { stages } from '@/store/dicts';
import { ProductInfo } from '@/store/products';
import { ProductSlot, SlotsByStages, useProductsSlotsStore } from '@/store/productsSlots';
import { Card } from 'ant-design-vue';
import { ExclamationCircleOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import { computed, onBeforeMount, onBeforeUpdate, onMounted, onUpdated, ref, Ref, watch } from 'vue';
import { usePlansStore } from '@/store/productsPlans';

const props = defineProps({
    product: {
        type: Object as () => ProductInfo,
        required: true
    }
});

const slotsStore = useProductsSlotsStore();
/**
 * Слоты данной продукции
 */
const slots: SlotsByStages = {
    1: [],
    2: []
};

const slotsReceieved: Ref<boolean> = ref(false);

const activeSlots: Array<number> = usePlansStore().getActiveSlots(props.product.product_id);

const highlight = (stage_id: number) => {
    return slots[stage_id].filter(
        (el: ProductSlot) => activeSlots.includes(el.product_slot_id)
    ).length > 0 ?
        "background: #50bb50;padding: 5px; color: white;" :
        "";
}
const amountFact = (stage_id: number) => {
    return usePlansStore().getAmountFact(activeSlots, stage_id);
}

onBeforeMount(async () => {
    if (!slotsReceieved.value && slots[1].length + slots[2].length == 0) {
        slots[1] = slotsStore.getByTypeAndProductID(props.product.product_id, 1)
        slots[2] = slotsStore.getPack(props.product.product_id)
        slotsReceieved.value = true
    } 
});
onMounted(() => {
    console.log("Mounted card " + props.product)
})

defineExpose(props);
</script>
<template>
    <Card draggable="true" class="draggable-card" :data-id="product.product_id" v-show="!product.hide"
        :key="product.product_id">
        <template #title>
            <span style="white-space: break-spaces;">{{ product.title }}</span>
        </template>
        <div class="hiding-data">
            <span v-if="product.order">Нужно обеспечить: <b>{{ product.order.amount ?? 0 }}</b><br></span>
            <div v-if="(slots[1].length + slots[2].length) > 0">
                <span>Этапы изготовления по линиям:</span>
                <ol>
                    <li v-if="slots[1].length > 0">
                        <span :style="highlight(1)">
                            {{ stages[1] }} ({{ amountFact(1) }})
                        </span>
                    </li>
                    <li v-if="slots[2].length > 0">
                        <span :style="highlight(2)">
                            {{ stages[2] }} ({{ amountFact(2) }})
                        </span>
                    </li>
                </ol>
            </div>
            <span v-else>
                <InfoCircleOutlined class="icon error" />
                Для данной продукции этапов нет
            </span>
        </div>
    </Card>
</template>