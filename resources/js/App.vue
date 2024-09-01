<script setup>
import Boards from './vueapp/boards.vue';
import TopBar from './vueapp/topbar.vue';
import Stats from './vueapp/stats.vue';
import { ref } from 'vue';
import { Modal } from 'ant-design-vue';
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
            boardKey: ref(1),
            statsRef: null
        }
    },
    methods: {
        showGraph() {
            this.openStats = true;
            return;
        },
        closeModal(ev) {
            this.openStats = false;
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
    <TopBar @showGraph="showGraph" />
    <Boards @data-recieved="getData" :key="boardKey" :data="data"/>
    <Stats :data="data" :open="openStats" @close-modal="closeModal"/>
</template>