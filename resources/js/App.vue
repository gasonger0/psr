<script setup lang="ts">
import Boards from './vueapp/components/boards/workers/board.vue'
import Stats from './vueapp/deprecated/stats.vue';
import Logs from './vueapp/deprecated/logs.vue';
import ProductsDict from './vueapp/deprecated/productsDict.vue';
import ProductsPlan from './vueapp/deprecated/productsPlan.vue';
import { ref, Ref } from 'vue';
import Result from './vueapp/deprecated/result.vue';
import WorkersWindow from './vueapp/components/modals/workers.vue';
import PlansDict from './vueapp/deprecated/plansDict.vue';
import Toolbar from './vueapp/components/toolbar/toolbar.vue';


const openStats: Ref<boolean> = ref(false);
const openResult: Ref<boolean> = ref(false);
const openLogs: Ref<boolean> = ref(false);
const openProductsDict: Ref<boolean> = ref(false);
const openWorkersDict: Ref<boolean> = ref(false);
const openPlansDict: Ref<boolean> = ref(false);
const boardKey: Ref<Number> = ref(1);
const boardType: Ref<boolean> = ref(false);
const boils: Ref<number> = ref(0); 

function showGraph() {
    openStats.value = true;
    return;
};
function showResult() {
    openResult.value = true;
    return;
};
function showLogs() {
    openLogs.value = true;
    return;
};
function showProductsDict() {
    openProductsDict.value = true;
    return;
};
function showWorkersDict() {
    openWorkersDict.value = true;
    return;
};
function showPlansDict() {
    openPlansDict.value = true;
    return;
};
function changeBoard() {
    boardType.value = !boardType;
};
function onGetBoils(ev) {
    boils.value = ev;
};
function closeModal(ev) {
    openStats.value = false;
    openResult.value = false;
    openLogs.value = false;
    openProductsDict.value = false;
    openWorkersDict.value = false;
    openPlansDict.value = false;
    if (ev) {
        boardKey.value += 1;
    }
};
</script>
<template>
    <Toolbar :boils="boils" :boardMode="boardType" @graph-window="showGraph" @result-window="showResult"
        @logs-window="showLogs" @products-window="showProductsDict" @workers-window="showWorkersDict"
        @change-board="changeBoard" @plans-window="showPlansDict" />
    <Result :data="data" :open="openResult" @close-modal="closeModal" @notify="notify" />
    <Stats :data="data" :open="openStats" @close-modal="closeModal" @notify="notify" />
    <Logs :open="openLogs" :lines="data ? data.lines : null" @close-modal="closeModal" @notify="notify" />
    <ProductsDict :open="openProductsDict" :data="data" @close-modal="closeModal" @notify="notify" />
    <WorkersWindow :open="openWorkersDict" @close-modal="closeModal" />
    <Boards v-if="!boardType" :key="boardKey" />
    <ProductsPlan v-if="boardType" :key="prodKey" :data="data" @getBoils="onGetBoils" @close-modal="closeModal"
        @notify="notify" />
    <PlansDict :open="openPlansDict" @close-modal="closeModal" />
</template>