<script setup lang="ts">
import WorkersBoard from '@boards/workers/board.vue';
import PlansBoard from '@boards/plans/board.vue';
import Stats from './vueapp/deprecated/stats.vue';
import Logs from './vueapp/deprecated/logs.vue';
import ProductsDict from '@modals/products.vue';
import ProductsPlan from './vueapp/deprecated/productsPlan.vue';
import { onBeforeMount, ref, Ref } from 'vue';
import Result from './vueapp/deprecated/result.vue';
import WorkersWindow from './vueapp/components/modals/workers.vue';
import Toolbar from '@common/toolbar.vue';
import { useModalsStore } from './vueapp/store/modal';
import { postRequest } from '@/functions';
import { useProductsStore } from '@/store/products';
import dayjs from 'dayjs';
import { useLinesStore } from '@/store/lines';
import { useWorkersStore } from '@/store/workers';
import { useResponsiblesStore } from '@/store/responsibles';
import { useCategoriesStore } from '@/store/categories';
import { useProductsSlotsStore } from '@/store/productsSlots';


// const modals = useModalsStore();
// const openModal = (modal: string) => {
//     modals.open(modal);
// }

const boardKey: Ref<Number> = ref(1);
const boardType: Ref<boolean> = ref(false);
const boils: Ref<number> = ref(0);

function changeBoard() {
    boardType.value = !boardType.value;
};
function onGetBoils(ev) {
    boils.value = ev;
};

onBeforeMount(async () => {
    if (sessionStorage.getItem('date') == null) {
        sessionStorage.setItem('date', (new Date()).toISOString().split('T')[0]);
    }
    if (sessionStorage.getItem('isDay') == null) {
        sessionStorage.setItem('isDay', '1');
    }
    await postRequest('/api/update_session', {
        date: sessionStorage.getItem('date'),
        isDay: sessionStorage.getItem('isDay')
    });

    await useLinesStore()._load();
    await useCategoriesStore()._load();
    await useProductsStore()._load({});
    await useProductsSlotsStore()._load();
    await useWorkersStore()._load();
    await useResponsiblesStore()._load();
    await useWorkersStore()._load();
})
</script>
<template>
    <Toolbar :boils="boils" :boardMode="boardType" @change-board="changeBoard" />
    <!-- <Result :data="data" :open="openResult" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Stats :data="data" :open="openStats" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Logs :open="openLogs" :lines="data ? data.lines : null" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <ProductsDict :open="openProductsDict" :data="data" @close-modal="closeModal" @notify="notify" /> -->
    <ProductsDict />
    <WorkersWindow />
    <WorkersBoard v-if="!boardType" />
    <PlansBoard v-else />
    <!-- <PlansDict /> -->
</template>