<script setup lang="ts">
import { logColumns } from '@/store/dicts';
import { useLogsStore } from '@/store/logs';
import { useModalsStore } from '@/store/modal';
import { FileExcelOutlined } from '@ant-design/icons-vue';
import { Button, Modal, Table } from 'ant-design-vue';

const modal = useModalsStore();
const logs = useLogsStore();
const exit = () => {
    modal.close('logs');
}

const loadLogs = () => {
    window.open('/api/logs/load', '_blank');
}
</script>
<template>
<Modal v-model:open="modal.visibility['logs']" @cancel="exit" :footer="false" style="width:50vw;">
    <Button type="primary" @click="loadLogs">
        <FileExcelOutlined/>
        Скачать XLSX
    </Button>
    <div class="table-container">
        <Table 
            :columns="logColumns"
            :data-source="logs.logs" />
    </div>
</Modal>
</template>