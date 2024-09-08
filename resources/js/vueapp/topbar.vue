<script setup>
import { ref } from 'vue';
import { Upload, Button } from 'ant-design-vue';
import { BarChartOutlined, UploadOutlined, EditOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
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
            let fd = new FormData();
            fd.append('file', file);

            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");


            axios.post('/api/load_xlsx', fd).then((response) => {
                if (response) {
                    console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            })
            console.log(file);
            return false;
        },
    },
    mounted() {
    }
}
</script>
<template>
    <div class="top-container">
        <Upload :v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processXlsx(ev)">
            <Button type="primary" style="background-color: green;">
                <UploadOutlined />
                Новый график
            </Button>
        </Upload>
        <Button type="default" @click="$emit('showResult')">
            <BarChartOutlined />
            Отчёт
        </Button>
        <Button type="primary" @click="$emit('showGraph')">
            <EditOutlined />
            Редактировать график
        </Button>
    </div>
</template>