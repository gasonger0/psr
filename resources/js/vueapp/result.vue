<script setup>
import { ref } from 'vue';
import { Modal, Select, SelectOption, Input } from 'ant-design-vue';
</script>
<script>
    export default {
        props: {
            data: {
                type: Object 
            },
            open: {
                type: Boolean
            }
        },
        data() {
            return {
                worker: ref(null),
                coef: ref(0)
            }
        },
        methods: {
            close() {
                this.$emit('close-modal');
            }
        }
    }
</script>
<template>
    <Modal v-model:open="$props.open" cancelText="Закрыть" okText="Сформировать отчёт"
    style="min-width:20vw; min-height: 30vh;" @ok="close(true)" @cancel="close(false)">
        Работник: &nbsp;
        <Select v-model:value="worker" style="width:100%;" type="number">
            <SelectOption v-for="(v, k) in $props.data.workers" v-model:value="v.worker_id" :label="v.title">{{ v.title }}</SelectOption>
        </Select>
        <br>
        <br>

        КТУ: <Input v-model:value="coef"/>
    </Modal>
</template>
