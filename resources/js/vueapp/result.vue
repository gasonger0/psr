<script setup>
import { ref } from 'vue';
import { Modal, Select, SelectOption, Input, Form } from 'ant-design-vue';
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
            close(send) {
                if (send) {
                    let fd = new FormData();
                    fd.append('worker_id', worker);
                    fd.append('ktu', coef);
                } else {
                    this.$emit('close-modal');
                }
            }
        }
    }
</script>
<template>
    <Modal v-model:open="$props.open" cancelText="Закрыть" okText="Сформировать отчёт"
    style="min-width:20vw; min-height: 30vh;" @ok="close(true)" @cancel="close(false)">
        <Select v-model:value="worker" style="width:100%;" type="number" label="Работник">
            <SelectOption v-for="(v, k) in $props.data.workers" v-model:value="v.worker_id" :label="v.title">{{ v.title }}</SelectOption>
        </Select>
        <br>
        <br>
        <Input v-model:value="coef" label="КТУ:"/>
    </Modal>
</template>
