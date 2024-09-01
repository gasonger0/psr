<script setup>
import { ref } from 'vue';
import { Upload, Button } from 'ant-design-vue';
import { BarChartOutlined, UploadOutlined, EditOutlined } from '@ant-design/icons-vue';
</script>
<script>
export default {
    data() {
        return {
            uploadedFile: ref(null)
        }
    },
    methods: {
        processXlsx(file) {
            console.log(file);
            if (file) {
                let fd = new FormData();
                fd.append('file', file);

                axios.post('/api/load_xlsx', fd).then((response) => {
                    if (response) {
                        console.log(response);
                    }
                })
            }
            return false;
        },
    },
    mounted() {
    }
}
</script>
<template>
    <div class="top-container">
        <Upload :v-model:file-list="uploadedFile" name="file" :before-upload="processXlsx">
            <Button type="primary" style="background-color: green;">
                <UploadOutlined />
                Новый график
            </Button>
        </Upload>
        <Button type="default">
            <BarChartOutlined />
            Отчёт
        </Button>
        <Button type="primary" @click="$emit('showGraph')">
            <EditOutlined />
            Редактировать график
        </Button>
    </div>
</template>