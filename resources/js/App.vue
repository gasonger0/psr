<script setup>
import Boards from './vueapp/boards.vue';
import TopBar from './vueapp/topbar.vue';
import Stats from './vueapp/stats.vue';
import Logs from './vueapp/logs.vue';
import ProductsDict from './vueapp/productsDict.vue';
import ProductsPlan from './vueapp/productsPlan.vue';
import { ref } from 'vue';
import Result from './vueapp/result.vue';
import { notification } from 'ant-design-vue';
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
            openProductsDict: ref(false),
            openProductsPlan: ref(false),
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
        showProductsDict() {
            this.openProductsDict = true;
            return;
        },
        showProductsPlan() {
            this.openProductsPlan = true;
            return;
        },
        closeModal(ev) {
            this.openStats = false;
            this.openResult = false;
            this.openLogs = false;
            this.openProductsDict = false;
            this.openProductsPlan = false;
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
    <ProductsDict
        :open="openProductsDict"
        @close-modal="closeModal"
        @notify="notify"/>
    <ProductsPlan
        :open="openProductsPlan"
        @close-modal="closeModal"
        @notify="notify"/>
    <TopBar 
        @showGraph="showGraph" 
        @showResult="showResult"
        @showLogs="showLogs" 
        @showProductsDict="showProductsDict"
        @showProductsPlan="showProductsPlan"
        @notify="notify"/>
    <Boards 
        :key="boardKey"
        @data-recieved="getData"  
        @notify="notify"/>
</template>