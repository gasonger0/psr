<script setup lang="ts">
import { useProductsStore } from '@/store/products';
import { ProductPlan, usePlansStore } from '@/store/productsPlans';
import { useProductsSlotsStore } from '@/store/productsSlots';
import { Card } from 'ant-design-vue';
import { computed, watch } from 'vue';
import { DeleteOutlined, EditOutlined } from '@ant-design/icons-vue';
import { useModalsStore } from '@/store/modal';

const props = defineProps({
    data: {
        type: Object as () => ProductPlan,
        required: true
    }
});
const modal = useModalsStore();

let slot = useProductsSlotsStore().getById(props.data.slot_id);
let product = useProductsStore().getByID(slot.product_id);

const boils = computed((): number => {
    return Number(
        (props.data.amount *
        eval(product.kg2boil) *
        eval(product.amount2parts) *
        eval(product.parts2kg)).toFixed(2)
    );
});
const beforeEdit = () => {
    if (slot.type_id == 1) {
        modal.boils[props.data.plan_product_id] = boils.value;
    };
    emit('edit', props.data);
}



const emit = defineEmits<{
    (e: 'edit', payload: ProductPlan): void;
}>();
</script>
<template>
    <Card class="draggable-card" :data-id="data.plan_product_id" :key="data.plan_product_id" draggable="true">
        <template #title>
            <div class="plan_card" v-if="data.started_at">
                <span>
                    {{ data.started_at.format('HH:mm') }} - {{ data.ended_at.format('HH:mm') }}
                </span>
                <Tooltip title="Убрать из плана">
                    <DeleteOutlined class="icon delete" @click="usePlansStore()._delete(data)" />
                </Tooltip>
                <Tooltip title="Редактировать">
                    <EditOutlined class="icon edit" @click="beforeEdit" />
                </Tooltip>
            </div>
        </template>
        <b v-if="slot.type_id == 1">Количество варок: {{ boils.toFixed(2) }}<br></b>
        <b style="margin-bottom: 10px;display: block;">Объём изготовления: {{ data.amount }}</b>
        <br>
        <span style="white-space: break-spaces;">{{ product.title }}</span>
    </Card>
</template>