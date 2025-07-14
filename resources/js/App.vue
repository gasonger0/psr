<script setup>
import Boards from './vueapp/components/worker_board/board.vue'
import Stats from './vueapp/deprecated/stats.vue';
import Logs from './vueapp/deprecated/logs.vue';
import ProductsDict from './vueapp/deprecated/productsDict.vue';
import ProductsPlan from './vueapp/deprecated/productsPlan.vue';
import { ref } from 'vue';
import Result from './vueapp/deprecated/result.vue';
import { notification } from 'ant-design-vue';
import WorkersWindow from './vueapp/components/workers_dict/window.vue';
import PlansDict from './vueapp/deprecated/plansDict.vue';
import Toolbar from './vueapp/components/toolbar/toolbar.vue';
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
            openWorkersDict: ref(false),
            openProductsPlan: ref(false),
            openPlansDict: ref(false),
            prod: ref(1),
            boardKey: ref(1),
            statsRef: null,
            boardType: ref(false),
            topBarKey: ref(1)
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
            return;
        },
        showProductsDict() {
            this.openProductsDict = true;
            return;
        },
        showWorkersDict(){
            this.openWorkersDict = true;
            return;
        },
        showPlansDict(){
            this.openPlansDict = true;
            return;
        },
        changeBoard(){
            this.boardType = !this.boardType;
        },
        onGetBoils(ev) {
            this.boils = ev;
            this.topBarKey += 1;
        },
        closeModal(ev) {
            this.openStats = false;
            this.openResult = false;
            this.openLogs = false;
            this.openProductsDict = false;
            this.openWorkersDict = false;
            this.openPlansDict = false; 
            // if (ev) {
            //     this.boardKey += 1;
            // }
            if (ev) {
                // this.prodKey += 1;
                this.boardKey += 1;
            }
        },
        getData(ev) {
            // console.log(ev);
            this.data = ev;
            // this.boardKey +=1;
        }
    }
}
</script>
<template>
    <Toolbar 
        :boils="boils"
        :boardMode="boardType"
        @graph-window="showGraph" 
        @result-window="showResult"
        @logs-window="showLogs" 
        @products-window="showProductsDict"
        @workers-window="showWorkersDict"
        @change-board="changeBoard"
        @plans-window="showPlansDict"/>
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
        :lines="data ? data.lines : null"
        @close-modal="closeModal"
        @notify="notify"/>
    <ProductsDict
        :open="openProductsDict"
        :data="data"
        @close-modal="closeModal"
        @notify="notify"/>
    <WorkersWindow
        :open="openWorkersDict"
        @close-modal="closeModal"
    />
    <Boards 
        v-if="!boardType"
        :key="boardKey"/>
    <ProductsPlan
        v-if="boardType"
        :key="prodKey"
        :data="data"
        @getBoils="onGetBoils"
        @close-modal="closeModal"
        @notify="notify"/>
    <PlansDict
        :open="openPlansDict"
        @close-modal="closeModal"/>
</template>