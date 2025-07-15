<script setup lang="ts">
import WorkersBoard from '@boards/workers/board.vue';
import PlansBoard from '@boards/plans/board.vue';
import Stats from './vueapp/deprecated/stats.vue';
import Logs from './vueapp/deprecated/logs.vue';
import ProductsDict from './vueapp/deprecated/productsDict.vue';
import ProductsPlan from './vueapp/deprecated/productsPlan.vue';
import { ref, Ref } from 'vue';
import Result from './vueapp/deprecated/result.vue';
import WorkersWindow from './vueapp/components/modals/workers.vue';
import PlansDict from './vueapp/deprecated/plansDict.vue';
import Toolbar from '@common/toolbar.vue';
import { useModalsStore } from './vueapp/store/modal';


const modals = useModalsStore();
const openModal = (modal: string) => {
    modals.open(modal);
}

const boardKey: Ref<Number> = ref(1);
const boardType: Ref<boolean> = ref(false);
const boils: Ref<number> = ref(0); 

function changeBoard() {
    boardType.value = !boardType.value;
};
function onGetBoils(ev) {
    boils.value = ev;
};

</script>
<template>
    <Toolbar :boils="boils" :boardMode="boardType" 
        @change-board="changeBoard" />
    <!-- <Result :data="data" :open="openResult" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Stats :data="data" :open="openStats" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <Logs :open="openLogs" :lines="data ? data.lines : null" @close-modal="closeModal" @notify="notify" /> -->
    <!-- <ProductsDict :open="openProductsDict" :data="data" @close-modal="closeModal" @notify="notify" /> -->
    <WorkersWindow />
    <WorkersBoard v-if="!boardType" />
    <PlansBoard v-else />
    <!-- <PlansDict /> -->
</template>