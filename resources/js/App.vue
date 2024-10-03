<script setup>
import Boards from './vueapp/boards.vue';
import TopBar from './vueapp/topbar.vue';
import Stats from './vueapp/stats.vue';
import { ref } from 'vue';
import Result from './vueapp/result.vue';
import { notification } from 'ant-design-vue';
import Logs from './vueapp/logs.vue';
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
            openLogs: ref(false),
            boardKey: ref(1),
            statsRef: null
        }
    },
    methods: {
        notify(type, message) {
            const n = () => {
                notification[type]({
                    message: message
                });
            }
            n();
            return;
        },
        showGraph() {
            this.openStats = true;
            return;
        },
        showResult() {
            this.openResult = true;
            return;
        },
        showLogs() {
            this.openLogs = true;
            console.log(this.openLogs);
            return;
        },
        closeModal(ev) {
            this.openStats = false;
            this.openResult = false;
            this.openLogs = false;
            if (ev) {
                this.boardKey += 1;
            }
        },
        getData(ev) {
            console.log(ev);
            this.data = ev;
            // this.boardKey +=1;
        }
    }
}
</script>
<template>
    <Result 
        :data="data" 
        :open="openResult" 
        @close-modal="closeModal" 
        @notify="notify"/>
    <Stats 
        :data="data" 
        :open="openStats" 
        @close-modal="closeModal" 
        @notify="notify"/>
    <Logs
        :open="openLogs"
        @close-modal="closeModal"
        @notify="notify"/>
    <TopBar 
        @showGraph="showGraph" 
        @showResult="showResult"
        @showLogs="showLogs" 
        @notify="notify"/>
    <Boards 
        :key="boardKey"
        @data-recieved="getData"  
        @notify="notify"/>
</template>