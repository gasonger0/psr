<script setup>
import Boards from './vueapp/boards.vue';
import TopBar from './vueapp/topbar.vue';
import Stats from './vueapp/stats.vue';
import { ref } from 'vue';
import Result from './vueapp/result.vue';
</script>
<script>
export default {
    components: {
        'Stats': Stats,
        'Boards': Boards
    },
    data() {
        return {
            data: null,
            openStats: ref(false),
            openResult: ref(false),
            boardKey: ref(1),
            statsRef: null
        }
    },
    methods: {
        showGraph() {
            this.openStats = true;
            return;
        },
        showResult() {
            this.openResult = true;
            return;
        },
        closeModal(ev) {
            this.openStats = false;
            this.openResult = false;
            //
        },
        getData(ev) {
            console.log(ev);
            this.data = ev;
        }
    }
}
</script>
<template>
    <Result :data="data" :open="openResult" @close-modal="closeModal"/>
    <Stats :data="data" :open="openStats" @close-modal="closeModal"/>
    <TopBar @showGraph="showGraph" @showResult="showResult"/>
    <Boards @data-recieved="getData" :key="boardKey" :data="data"/>
</template>