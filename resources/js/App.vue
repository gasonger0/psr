<script setup lang="ts">
import WorkersBoard from '@boards/workers/board.vue';
import PlansBoard from '@boards/plans/board.vue';
import ProductsDict from '@modals/products.vue';
import { onBeforeMount, ref, Ref } from 'vue';
import WorkersWindow from './vueapp/components/modals/workers.vue';
import Toolbar from '@common/toolbar.vue';
import { getTimeString, postRequest } from '@/functions';
import { useProductsStore } from '@/store/products';
import { useLinesStore } from '@/store/lines';
import { useWorkersStore } from '@/store/workers';
import { useResponsiblesStore } from '@/store/responsibles';
import { useCategoriesStore } from '@/store/categories';
import { useProductsSlotsStore } from '@/store/productsSlots';
import { usePlansStore } from '@/store/productsPlans';
import { useWorkerSlotsStore } from '@/store/workerSlots';
import Loading from '@common/loading.vue';
import Graph from '@modals/graph.vue';
import { useCompaniesStore } from '@/store/companies';
import { useLogsStore } from '@/store/logs';
import Logs from '@modals/logs.vue';


// const modals = useModalsStore();
// const openModal = (modal: string) => {
//     modals.open(modal);
// }

const boardType: Ref<boolean> = ref(false);
const isReady: Ref<boolean> = ref(false);

function changeBoard() {
    boardType.value = !boardType.value;
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
    await useCompaniesStore()._load();
    await useWorkersStore()._load();
    await useWorkerSlotsStore()._load();
    await processData();
    isReady.value = true;
    await useResponsiblesStore()._load();
    await useCategoriesStore()._load();
    await useProductsStore()._load(0);
    let products_ids = useProductsStore().products.map(i => i.product_id);
    await useProductsSlotsStore()._load(products_ids);
    await usePlansStore()._load();
    await useLogsStore()._load();
});

const processData = async () => {
    return new Promise((resolve, reject) => {
        let ts = getTimeString();
        useWorkerSlotsStore().slots.forEach(slot => {
            if (slot.started_at <= ts && ts <= slot.ended_at) {
                let worker = useWorkersStore().getByID(slot.worker_id);
                worker!.current_line_id = slot.line_id;
                worker!.current_slot_id = slot.slot_id;
                slot.popover = ref(false);
            }
        });

        resolve(true);
    });
};
</script>
<template>
    <Toolbar :boardMode="boardType" @change-board="changeBoard" />
    <!-- <Result :data="data" :open="openResult" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Stats :data="data" :open="openStats" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Logs :open="openLogs" :lines="data ? data.lines : null" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <ProductsDict :open="openProductsDict" :data="data" @close-modal="closeModal" @notify="notify" /> -->
    <ProductsDict />
    <WorkersWindow />
    <Graph />
    <WorkersBoard v-if="!boardType && isReady" />
    <PlansBoard v-else-if="isReady" />
    <Logs />
    <Loading v-show="!isReady"/>
</template>