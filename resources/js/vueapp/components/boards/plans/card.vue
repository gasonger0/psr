<script setup lang="ts">
import { ProductInfo } from '@/store/products';
import { Card } from 'ant-design-vue';

const props = defineProps({
    product: {
        type: Object as () => ProductInfo,
        required: true
    }
});


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
                <ExclamationCircleOutlined style="font-size:20px;color:#f00d0d;position:absolute;right:10px;" />
            </span>
            <span v-else-if="product.errors >= 1">
                <InfoCircleOutlined style="font-size:20px;color:#ff8f00;position:absolute;right:10px;" />
            </span>
            <span>Нужно обеспечить: <b>{{ product.order_amount }}</b></span>
            <br>
            <span>Этапы изготовления по линиям:</span>
            <ol v-if="(v.slots[1].length + v.slots[2].length) > 0">
                <li v-if="v.slots[1].length > 0">
                    <span :style="v.active_slots[1] ? 'background: #50bb50;padding: 5px; color: white;' : ''">
                        {{ stages[1] }} ({{ v.amounts_fact[0] }})
                    </span>
                </li>
                <li v-if="v.slots[2].length > 0">
                    <span :style="v.active_slots[2] ? 'background: #50bb50;padding: 5px; color: white;' : ''">
                        {{ stages[2] }} ({{ v.amounts_fact[1] }})
                    </span>
                </li>
            </ol>
        </div>
    </Card>
</template>