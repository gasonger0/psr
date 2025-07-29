<script setup lang="ts">
import { stages } from '@/store/dicts';
import { ProductInfo } from '@/store/products';
import { ProductSlot, SlotsByStages, useProductsSlotsStore } from '@/store/productsSlots';
import { Card } from 'ant-design-vue';
import { ExclamationCircleOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import { computed } from 'vue';
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

for(let i in stages) {
    slots[i] = slotsStore.getByTypeAndProductID(props.product.product_id, Number(i));
}

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

defineExpose(props);
</script>
<template>
    <Card draggable="true" class="draggable-card" :data-id="product.product_id"
        v-show="!product.hide" :key="product.product_id">
        <template #title>
            <span style="white-space: break-spaces;">{{ product.title }}</span>
        </template>
        <div class="hiding-data">
            <span v-if="product.errors >= 3">
                <ExclamationCircleOutlined class="icon warn"/>
            </span>
            <span v-else-if="product.errors >= 1">
                <InfoCircleOutlined class="icon error" />
            </span>
            <span v-if="product.order">Нужно обеспечить: <b>{{ product.order.amount }}</b><br></span>
            <span>Этапы изготовления по линиям:</span>
            <ol v-if="(slots[1].length + slots[2].length) > 0">
                <li v-for="(v, k) in stages">
                    <span :style="highlight(k)">
                        {{ stages[k] }} ({{ amountFact(k) }})
                    </span>
                </li>
            </ol>
        </div>
    </Card>
</template>